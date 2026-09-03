
(function($){
    'use strict';

    const state = {
        restaurant: null,
        table: null,
        categories: [],
        items: [],
        payment: null,
        reviews: null,
        reviewUrl: '',
        cart: [],
        selectedPay: 'cash',
        upiPaymentAttempted: false,
        upiPaymentApp: '',
        lastOrderId: 0,
        kitchenLastSeen: [],
        kitchenKnownIds: [],
        kitchenAudioUnlocked: false,
        kitchenPollTimer: null,
        kitchenPollBusy: false,
        kitchenLastRenderedIds: [],
        kitchenLastRequestAt: 0,
        billSessionToken: '',
        coupon: null,
        room: null,
        orderSource: 'table_qr',
    };

    function currencySymbol(){
        if(state.restaurant && state.restaurant.currency_symbol){ return String(state.restaurant.currency_symbol); }
        if(state.restaurant && state.restaurant.settings && state.restaurant.settings.currency_symbol){ return String(state.restaurant.settings.currency_symbol); }
        return '₹';
    }

    function money(value){
        return currencySymbol() + Number(value || 0).toFixed(2);
    }

    function escapeHtml(value){
        return String(value === null || value === undefined ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }


    function hasReviews(){
        return !!(state.reviews && state.reviews.enabled && (state.reviewUrl || state.reviews.url));
    }

    function reviewUrl(fallback){
        return fallback || state.reviewUrl || (state.reviews && state.reviews.url) || '#';
    }

    function reviewButton(label, url, extraClass){
        if(!hasReviews() && !url){ return ''; }
        const text = label || (state.reviews && state.reviews.button_text) || 'Review us on Google';
        return `<a class="menuqr-review-btn ${extraClass || ''}" target="_blank" rel="noopener" href="${escapeHtml(reviewUrl(url))}">⭐ ${escapeHtml(text)}</a>`;
    }

    function syncReviewHeader(){ return; }

    function requestedTableId(){
        const rootValue = Number($('#menuqr-customer-app').data('table-id') || 0);
        if(rootValue > 0){ return rootValue; }
        try {
            const params = new URLSearchParams(window.location.search || '');
            const candidates = ['t', 'table', 'table_id', 'table_no', 'table_number'];
            for(let i = 0; i < candidates.length; i += 1){
                const raw = String(params.get(candidates[i]) || '').trim();
                const numeric = Number(raw.replace(/\D+/g, ''));
                if(numeric > 0){ return numeric; }
            }
        } catch(error){}
        return 0;
    }

    function requestedRoomId(){
        const rootValue = Number($('#menuqr-customer-app').data('room-id') || 0);
        if(rootValue > 0){ return rootValue; }
        try {
            const params = new URLSearchParams(window.location.search || '');
            const candidates = ['room', 'room_id', 'room_no', 'room_number'];
            for(let i = 0; i < candidates.length; i += 1){
                const raw = String(params.get(candidates[i]) || '').trim();
                const numeric = Number(raw.replace(/\D+/g, ''));
                if(numeric > 0){ return numeric; }
            }
        } catch(error){}
        return 0;
    }


    function urlParam(name){
        try { return String(new URLSearchParams(window.location.search || '').get(name) || '').trim(); } catch(error){ return ''; }
    }

    function getServiceContext(){
        const root = $('#menuqr-customer-app');
        const payloadRoomId = Number((state.room && (state.room.id || state.room.room_id)) || 0);
        const payloadTableId = Number((state.table && (state.table.id || state.table.table_id)) || 0);
        const declaredSource = String(root.attr('data-order-source') || root.data('order-source') || urlParam('source') || urlParam('order_source') || '').toLowerCase();
        let roomId = Math.max(0, requestedRoomId(), payloadRoomId, Number(root.data('room-id') || 0));
        let tableId = Math.max(0, requestedTableId(), payloadTableId, Number(root.data('table-id') || 0));

        if(declaredSource === 'room' || declaredSource === 'room_qr' || declaredSource === 'hotel_room'){
            tableId = 0;
        } else if(declaredSource === 'table' || declaredSource === 'table_qr'){
            roomId = 0;
        } else if(roomId > 0){
            tableId = 0;
        }

        const orderSource = roomId > 0 ? 'room_qr' : 'table_qr';
        const serviceId = roomId > 0 ? roomId : tableId;
        root.attr('data-order-source', orderSource).data('order-source', orderSource);
        root.attr('data-room-id', String(roomId)).data('room-id', roomId);
        root.attr('data-table-id', String(tableId)).data('table-id', tableId);
        return { restaurantId: Number(root.data('restaurant-id') || 0), tableId, roomId, orderSource, serviceId };
    }

    function resolveServiceLabel(table, room, orderSource){
        const root = $('#menuqr-customer-app');

        if(orderSource === 'room_qr'){
            const rootRoomId = requestedRoomId();
            const roomCandidates = [];
            if(room && typeof room === 'object'){
                roomCandidates.push(room.room_number, room.room_name, room.label, room.name, room.id);
            }
            roomCandidates.push(root.attr('data-room-label'), root.data('room-label'), root.attr('data-room-name'), root.data('room-name'), root.data('room-id'));
            for(let i = 0; i < roomCandidates.length; i += 1){
                let raw = String(roomCandidates[i] === undefined || roomCandidates[i] === null ? '' : roomCandidates[i]).trim();
                if(!raw || raw === '—' || /^room\s*n\/a$/i.test(raw)){ continue; }
                raw = raw.replace(/^room\s*/i, '').trim();
                if(raw){ return 'Room ' + raw; }
            }
            if(rootRoomId > 0){ return 'Room ' + rootRoomId; }
            return 'Room';
        }

        const rootTableId = requestedTableId();
        const tableCandidates = [];
        if(table && typeof table === 'object'){
            tableCandidates.push(table.table_number, table.table_no, table.table_name, table.table_code, table.name, table.label, table.slug, table.id);
        }
        tableCandidates.push(root.attr('data-table-label'), root.data('table-label'), root.attr('data-table-name'), root.data('table-name'), root.data('table-id'));
        for(let i = 0; i < tableCandidates.length; i += 1){
            let raw = String(tableCandidates[i] === undefined || tableCandidates[i] === null ? '' : tableCandidates[i]).trim();
            if(!raw || raw === '—' || /^table\s*n\/a$/i.test(raw)){ continue; }
            raw = raw.replace(/^table\s*/i, '').trim();
            if(/^t\d+$/i.test(raw)){ raw = raw.replace(/^t/i, '').trim(); }
            if(raw){ return 'Table ' + raw; }
        }
        if(rootTableId > 0){ return 'Table ' + rootTableId; }
        return 'Table';
    }

    function syncDrawerSummary(){
        return;
    }

    function closeActionDrawer(){
        $('body').removeClass('menuqr-drawer-open');
    }

    function openActionDrawer(){
        return;
    }

    function renderRestaurantMenuLogo(restaurant){
        const $logo = $('#m-restaurant-logo');
        if(!$logo.length){ return; }
        const name = (restaurant && restaurant.name) ? String(restaurant.name) : 'Restaurant';
        const initial = name.trim().charAt(0).toUpperCase() || 'R';
        const logo = restaurant && restaurant.logo ? String(restaurant.logo) : '';
        if(logo){
            $logo.html(`<img src="${escapeHtml(logo)}" alt="${escapeHtml(name)} logo">`).addClass('has-image');
        } else {
            $logo.html(`<span>${escapeHtml(initial)}</span>`).removeClass('has-image');
        }
        $('#m-footer-rest-name').text(name);
    }

    function fallbackMenuPayload(restaurantId, serviceId, orderSource){
        return {
            restaurant: {
                id: Number(restaurantId || 0),
                name: '',
                logo: '',
                description: ''
            },
            table: orderSource === 'table_qr' ? {
                id: Number(serviceId || 0),
                table_number: String(serviceId || '')
            } : null,
            room: orderSource === 'room_qr' ? {
                id: Number(serviceId || 0),
                room_number: String(serviceId || '')
            } : null,
            order_source: orderSource || 'table_qr',
            categories: [],
            items: [],
            payment: {},
            reviews: {enabled: 0, url: '#', button_text: 'Review', message: ''},
            review_url: '#'
        };
    }

    function applyMenuPayload(data){
        state.restaurant = data.restaurant || null;
        state.table = data.table || null;
        state.room = data.room || null;
        state.orderSource = data.order_source || (state.room ? 'room_qr' : 'table_qr');
        state.categories = data.categories || [];
        state.items = data.items || [];
        state.payment = data.payment || {};
        state.reviews = data.reviews || {};
        state.reviewUrl = data.review_url || '';

        const restaurantName = state.restaurant && state.restaurant.name ? String(state.restaurant.name) : 'Restaurant';
        const serviceLabel = resolveServiceLabel(state.table, state.room, state.orderSource);
        const hasConcreteLabel = /^(Table|Room)\s+.+/i.test(serviceLabel) && !/^(Table|Room)\s*$/i.test(serviceLabel);

        $('#m-rest-name').text(restaurantName);
        $('#m-table-info')
            .text(serviceLabel)
            .toggleClass('is-empty', !hasConcreteLabel)
            .attr('title', serviceLabel)
            .attr('data-table-label', serviceLabel);
        const root = $('#menuqr-customer-app');
        root
            .attr('data-table-label', state.orderSource === 'table_qr' ? serviceLabel : '')
            .attr('data-room-label', state.orderSource === 'room_qr' ? serviceLabel : '');
        if(state.orderSource === 'room_qr'){
            const resolvedRoomId = Number((state.room && (state.room.id || state.room.room_id)) || requestedRoomId() || 0);
            root.attr('data-room-id', String(resolvedRoomId)).data('room-id', resolvedRoomId);
            root.attr('data-table-id', '0').data('table-id', 0);
        } else {
            const resolvedTableId = Number((state.table && (state.table.id || state.table.table_id)) || requestedTableId() || 0);
            root.attr('data-table-id', String(resolvedTableId)).data('table-id', resolvedTableId);
            root.attr('data-room-id', '0').data('room-id', 0);
        }
        $('#m-footer-rest-name').text(restaurantName);
        $('#m-footer-rest-copy').text((state.restaurant && (state.restaurant.tagline || state.restaurant.description)) ? (state.restaurant.tagline || state.restaurant.description) : 'Browse the menu, add items to cart, review your bill and place your order.');
        $('#m-rest-subtitle').text((state.restaurant && (state.restaurant.tagline || state.restaurant.description)) ? (state.restaurant.tagline || state.restaurant.description) : 'Good Food, Good Mood');

        renderRestaurantMenuLogo(state.restaurant || {});
        categoryTabs();
        renderItems(0);
        renderCart();
        renderCheckout();
        renderRestaurantReviewCard();

        const cartQty = Number((state.cart || []).reduce((sum, item) => sum + Number(item.qty || 0), 0));
        $('#m-cart-count').text(cartQty);

        if(hasReviews()){
            const reviewText = (state.reviews && state.reviews.button_text) || 'Review';
            const reviewHref = reviewUrl();
            $('#m-review-chip-link').attr('href', reviewHref).removeAttr('hidden').attr('aria-label', reviewText).attr('title', reviewText);
        } else {
            $('#m-review-chip-link').attr('hidden', 'hidden');
        }
    }

    function renderRestaurantReviewCard(){
        const card = $('#menuqr-restaurant-review-card');
        if(!card.length){ return; }
        if(!hasReviews()){
            card.attr('hidden', 'hidden').empty();
            return;
        }
        const text = (state.reviews && state.reviews.button_text) || 'Write a Review';
        card.removeAttr('hidden').html(`
            <div class="menuqr-review-copy"><div class="star">⭐</div><div><b>Love our food?</b><span>Share your experience with us</span></div></div>
            <a class="menuqr-review-btn" target="_blank" rel="noopener" href="${escapeHtml(reviewUrl())}">${escapeHtml(text)}</a>
        `);
    }


    function randomToken(){
        if(window.crypto && window.crypto.getRandomValues){
            const bytes = new Uint8Array(24);
            window.crypto.getRandomValues(bytes);
            return Array.from(bytes).map(b => b.toString(16).padStart(2, '0')).join('');
        }
        return 'mqr_' + Date.now() + '_' + Math.random().toString(36).slice(2);
    }

    function billStorageKey(){
        const service = getServiceContext();
        const source = service.roomId > 0 ? 'room' : 'table';
        return 'menuqr_bill_session_' + service.restaurantId + '_' + source + '_' + service.serviceId;
    }

    function ensureBillSessionToken(){
        if(state.billSessionToken){ return state.billSessionToken; }
        const key = billStorageKey();
        let stored = null;
        try { stored = JSON.parse(localStorage.getItem(key) || 'null'); } catch(e){ stored = null; }
        const now = Date.now();
        const maxAge = 4 * 60 * 60 * 1000;
        if(stored && stored.token && stored.createdAt && (now - Number(stored.createdAt)) < maxAge){
            state.billSessionToken = stored.token;
            return state.billSessionToken;
        }
        state.billSessionToken = randomToken();
        try { localStorage.setItem(key, JSON.stringify({token: state.billSessionToken, createdAt: now})); } catch(e){}
        return state.billSessionToken;
    }

    function persistCustomerDetails(){
        const key = billStorageKey() + '_customer';
        const data = {
            name: $('#co-customer-name').val() || '',
            whatsapp: $('#co-customer-whatsapp').val() || ''
        };
        try { localStorage.setItem(key, JSON.stringify(data)); } catch(e){}
    }

    function restoreCustomerDetails(){
        const key = billStorageKey() + '_customer';
        let data = null;
        try { data = JSON.parse(localStorage.getItem(key) || 'null'); } catch(e){ data = null; }
        if(data){
            if(data.name && !$('#co-customer-name').val()){ $('#co-customer-name').val(data.name); }
            if(data.whatsapp && !$('#co-customer-whatsapp').val()){ $('#co-customer-whatsapp').val(data.whatsapp); }
        }
    }

    function renderBillHistory(data){
        const box = $('#menuqr-bill-history');
        if(!box.length){ return; }

        if(!data || !data.bill){
            box.removeAttr('hidden').html(`
                <div class="section-card">
                    <div class="section-title">Your Running Bill</div>
                    <div class="empty-state"><span class="empty-icon">🧾</span><h4>No running bill yet</h4><p>Place an order and this 4-hour bill history will appear here.</p></div>
                    <button class="btn btn-outline btn-full" type="button" id="menuqr-close-bill-history">Back to Menu</button>
                </div>
            `);
            return;
        }

        const bill = data.bill;
        const session = data.session || {};
        const orders = data.orders || [];
        const items = bill.items_snapshot ? (typeof bill.items_snapshot === 'string' ? JSON.parse(bill.items_snapshot || '[]') : bill.items_snapshot) : [];
        const expiresAt = session.expires_at ? escapeHtml(session.expires_at) : '4 hours from first order';
        const itemRows = (items || []).map(item => `
            <div class="bill-mini-item">
                <span>${escapeHtml(item.emoji || '🍽️')} ${escapeHtml(item.name || 'Item')} ×${Number(item.qty || 1)}</span>
                <strong>${money(item.total || 0)}</strong>
            </div>
        `).join('');

        box.removeAttr('hidden').html(`
            <div class="section-card running-bill-card">
                <div class="section-title">
                    <span>🧾 Your 4-Hour Running Bill</span>
                    <span class="badge ${bill.payment_status === 'paid' ? 'badge-paid' : 'badge-unpaid'}">${escapeHtml(String(bill.payment_status || 'unpaid').toUpperCase())}</span>
                </div>
                <div class="bill-mini-meta">
                    <div><strong>Bill:</strong> ${escapeHtml(bill.bill_number || '-')}</div>
                    <div><strong>Expires:</strong> ${expiresAt}</div>
                    <div><strong>Orders:</strong> ${orders.length}</div>
                </div>
                <div class="bill-mini-items">${itemRows || '<p class="text-muted">No items yet.</p>'}</div>
                <div class="bill-mini-total"><span>Total</span><strong>${money(bill.grand_total || 0)}</strong></div>
                ${(data.review && data.review.enabled && data.review_url && data.review.show_on_bill) ? `<div class="menuqr-review-card"><strong>⭐ Loved your meal?</strong><p>${escapeHtml(data.review.message || 'Your honest Google review helps us improve.')}</p>${reviewButton(data.review.button_text, data.review_url, 'btn-full')}</div>` : ''}
                <div class="form-row" style="margin-top:12px;">
                    <input class="form-input" id="bill-customer-name" placeholder="Name" value="${escapeHtml(session.customer_name || '')}">
                    <input class="form-input" id="bill-customer-whatsapp" placeholder="WhatsApp number" value="${escapeHtml(session.customer_whatsapp || '')}">
                </div>
                <div class="mq-actions-center" style="display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;">
                    <button class="btn btn-primary" id="menuqr-save-bill-customer" type="button">Save WhatsApp</button>
                    ${data.bill_url ? `<a class="btn btn-outline" target="_blank" rel="noopener" href="${escapeHtml(data.bill_url)}">Open Bill</a>` : ''}
                    ${data.print_url ? `<a class="btn btn-teal" target="_blank" rel="noopener" href="${escapeHtml(data.print_url)}">Print Bill</a>` : ''}
                    <button class="btn btn-ghost" type="button" id="menuqr-close-bill-history">Back to Menu</button>
                </div>
            </div>
        `);
    }



    function customerBillPageUrl(){
        const service = getServiceContext();
        const token = ensureBillSessionToken();
        const base = (window.menuqr_ajax && (window.menuqr_ajax.bill_url || window.menuqr_ajax.bill_page_url))
            ? String(window.menuqr_ajax.bill_url || window.menuqr_ajax.bill_page_url)
            : new URL('/bill/', window.location.origin).toString();
        const url = new URL(base, window.location.origin);
        url.searchParams.set('r', String(service.restaurantId || 0));
        url.searchParams.set('session', token);
        url.searchParams.set('order_source', service.orderSource || (service.roomId > 0 ? 'room_qr' : 'table_qr'));
        if(service.roomId > 0 || service.orderSource === 'room_qr'){
            url.searchParams.set('room_id', String(service.roomId || 0));
            url.searchParams.delete('t');
            url.searchParams.delete('table_id');
        } else {
            url.searchParams.set('t', String(service.tableId || 0));
            url.searchParams.delete('room_id');
        }
        return url.toString();
    }

    function openCustomerBillPage(){
        closeActionDrawer();
        window.location.href = customerBillPageUrl();
    }

    function loadBillHistory(){
        const root = $('#menuqr-customer-app');
        if(!root.length){ return; }
        $('#m-items-grid').attr('hidden','hidden');
        $('#m-cat-strip').attr('hidden','hidden');
        $('#menuqr-bill-history').removeAttr('hidden').html('<div class="section-card">Loading bill…</div>');
        $.ajax({
            url: menuqr_ajax.ajax_url,
            type: 'POST',
            cache: false,
            data: {
                action: 'menuqr_get_customer_bill',
                nonce: menuqr_ajax.nonce,
                restaurant_id: Number(root.data('restaurant-id') || 0),
                table_id: Number(root.data('table-id') || 0),
                room_id: Number(root.data('room-id') || 0),
                order_source: String(root.data('order-source') || ''),
                bill_session_token: ensureBillSessionToken(),
                _t: Date.now()
            }
        }).done(function(response){
            renderBillHistory(response && response.success ? response.data : null);
        }).fail(function(){
            renderBillHistory(null);
        });
    }

    function closeBillHistory(){
        $('#menuqr-bill-history').attr('hidden','hidden').empty();
        $('#m-items-grid').removeAttr('hidden');
        $('#m-cat-strip').removeAttr('hidden');
    }

    function saveBillCustomer(){
        const service = getServiceContext();
        $.post(menuqr_ajax.ajax_url, {
            action: 'menuqr_update_bill_customer',
            nonce: menuqr_ajax.nonce,
            restaurant_id: service.restaurantId,
            table_id: service.tableId,
            room_id: service.roomId,
            order_source: service.orderSource,
            bill_session_token: ensureBillSessionToken(),
            customer_name: $('#bill-customer-name').val() || '',
            customer_whatsapp: $('#bill-customer-whatsapp').val() || '',
            _t: Date.now()
        }).done(function(response){
            renderBillHistory(response && response.success ? response.data : null);
        });
    }


    function getCategoryId(item){
        return Number(item.category_id || item.cid || item.category || 0);
    }

    function getCategoryName(categoryId){
        const cat = (state.categories || []).find(entry => Number(entry.id) === Number(categoryId));
        return cat ? cat.name : 'Menu';
    }

    function getItemImage(item){
        return item.image || item.image_url || item.item_image || '';
    }

    function getFilteredMenuItems(categoryId){
        const selectedCategoryId = Number(categoryId || 0);
        const search = String($('#m-menu-search').val() || '').trim().toLowerCase();
        return (state.items || []).filter(item => {
            const itemCategoryId = getCategoryId(item);
            const categoryMatch = !selectedCategoryId || itemCategoryId === selectedCategoryId;
            const searchable = [item.name, item.description, getCategoryName(itemCategoryId)].join(' ').toLowerCase();
            const searchMatch = !search || searchable.indexOf(search) !== -1;
            return categoryMatch && searchMatch;
        });
    }

    function customerViews(){
        return {
            menu: $('#v-menu'),
            cart: $('#menuqr-cart-wrap'),
            checkout: $('#menuqr-checkout-wrap'),
            status: $('#menuqr-order-status-wrap')
        };
    }

    function setActiveView(view){
        const requestedView = view || 'menu';
        const views = customerViews();
        const app = $('#menuqr-customer-app');

        Object.keys(views).forEach(key => {
            const element = views[key];
            if(element.length){
                element.attr('hidden', 'hidden')
                    .removeClass('active is-active')
                    .attr('aria-hidden', 'true')
                    .css('display', 'none');
            }
        });

        if(app.length){
            if(requestedView === 'menu'){
                app.removeAttr('hidden')
                    .removeClass('menuqr-hidden is-hidden')
                    .attr('aria-hidden', 'false')
                    .css({
                        display: '',
                        minHeight: ''
                    });
            }else{
                app.attr('hidden', 'hidden')
                    .addClass('menuqr-hidden is-hidden')
                    .attr('aria-hidden', 'true')
                    .css({
                        display: 'none',
                        minHeight: '0',
                        height: '0',
                        margin: '0',
                        padding: '0',
                        overflow: 'hidden'
                    });
            }
        }

        const target = views[requestedView] || views.menu;
        if(target && target.length){
            target.removeAttr('hidden')
                .addClass('active is-active')
                .attr('aria-hidden', 'false')
                .css('display', 'block');
        }

        $('body')
            .toggleClass('menuqr-cart-open', requestedView === 'cart')
            .toggleClass('menuqr-checkout-open', requestedView === 'checkout')
            .toggleClass('menuqr-status-open', requestedView === 'status')
            .toggleClass('menuqr-menu-open', requestedView === 'menu');

        updateStickyCartBar();
        try { window.scrollTo({top: 0, behavior: 'smooth'}); } catch(e) { window.scrollTo(0, 0); }
    }

    function ensureStickyCartBar(){
        if(!$('#menuqr-view-cart-bar').length && $('#menuqr-customer-app').length){
            $('#menuqr-customer-app').after(`
                <button type="button" id="menuqr-view-cart-bar" class="menuqr-view-cart-bar" data-menuqr-go="cart" hidden>
                    <span class="menuqr-view-cart-copy">View Cart</span>
                    <strong id="menuqr-view-cart-total">₹0.00</strong>
                </button>
            `);
        }
    }

    function updateStickyCartBar(){
        ensureStickyCartBar();
        const bar = $('#menuqr-view-cart-bar');
        if(!bar.length){ return; }
        const itemCount = state.cart.reduce((sum, entry) => sum + Number(entry.qty || 0), 0);
        const subtotal = state.cart.reduce((sum, entry) => sum + (Number(entry.price || 0) * Number(entry.qty || 0)), 0);
        const menuVisible = !$('#v-menu').attr('hidden') && $('#v-menu').is(':visible');
        if(itemCount > 0 && menuVisible){
            $('#menuqr-view-cart-total').text(`${itemCount} item${itemCount === 1 ? '' : 's'} • ${money(subtotal)}`);
            bar.removeAttr('hidden').addClass('show').css('display', 'flex');
        } else {
            bar.attr('hidden', 'hidden').removeClass('show').css('display', 'none');
        }
    }

    function categoryTabs(){
        const counts = {};
        (state.items || []).forEach(item => {
            const categoryId = getCategoryId(item);
            counts[categoryId] = (counts[categoryId] || 0) + 1;
        });

        const tabs = [{id: 0, name: 'All Items', count: (state.items || []).length}]
            .concat((state.categories || []).map(cat => ({
                id: Number(cat.id),
                name: cat.name,
                count: counts[Number(cat.id)] || 0
            })));

        $('#m-cat-strip').html(tabs.map((cat, i) => `
            <button type="button" class="cat-pill ${i===0?'active':''}" data-category-id="${cat.id || 0}">
                <span>${escapeHtml(cat.name)}</span>
                <b>${Number(cat.count || 0)}</b>
            </button>
        `).join(''));

        if(!$('#m-menu-search').length){
            $('#m-cat-strip').after(`
                <div class="m-menu-tools">
                    <label class="m-search-wrap">
                        <span>🔎</span>
                        <input id="m-menu-search" type="search" placeholder="Search dishes, drinks, desserts..." autocomplete="off">
                    </label>
                    <button type="button" class="m-clear-filter" data-menu-clear-filter aria-label="Clear filter">All menu</button>
                </div>
            `);
        }
    }

    function renderItems(categoryId){
        const selectedCategoryId = Number(categoryId || 0);
        const items = getFilteredMenuItems(selectedCategoryId);
        const activeLabel = selectedCategoryId ? getCategoryName(selectedCategoryId) : 'All Items';

        if(!items.length){
            $('#m-items-grid').html(`
                <div class="m-empty-menu">
                    <div class="m-empty-icon">🍽️</div>
                    <h3>No items found</h3>
                    <p>${selectedCategoryId ? 'This category has no available menu items yet.' : 'No menu items are available right now.'}</p>
                    <button type="button" class="btn btn-primary btn-sm" data-menu-clear-filter>Show all items</button>
                </div>
            `);
            return;
        }

        const cards = items.map(item => {
            const existing = state.cart.find(entry => Number(entry.item_id) === Number(item.id));
            const qty = existing ? existing.qty : 0;
            const available = Number(item.is_available) === 1;
            const image = getItemImage(item);
            const catName = getCategoryName(getCategoryId(item));
            const featured = Number(item.is_featured) === 1 ? '<span class="m-featured">Popular</span>' : '';
            const rawFoodType = String(item.food_type || item.item_type || item.type || (Number(item.is_veg) === 0 ? 'nonveg' : 'veg')).toLowerCase().replace(/[^a-z]/g, '');
            const foodType = rawFoodType === 'nonveg' || rawFoodType === 'nonvegetarian' ? 'nonveg' : (rawFoodType === 'egg' ? 'egg' : 'veg');
            const foodLabel = foodType === 'nonveg' ? '🔴 Non-Veg' : (foodType === 'egg' ? '🟠 Egg' : '🟢 Veg');
            const action = available
                ? (qty > 0 ? `<div class="qty-ctrl"><button class="qty-btn" data-qty="minus" data-item-id="${item.id}" aria-label="Decrease quantity">−</button><span class="qty-num">${qty}</span><button class="qty-btn" data-qty="plus" data-item-id="${item.id}" aria-label="Increase quantity">+</button></div>` : `<button class="add-btn" data-add-item="${item.id}">Add +</button>`)
                : `<span class="badge badge-cancelled">Out of stock</span>`;
            const media = image
                ? `<img src="${escapeHtml(image)}" alt="${escapeHtml(item.name)}" class="menu-card-img" loading="lazy" />`
                : `<div class="menu-card-emoji">${escapeHtml(item.emoji || '🍽️')}</div>`;
            const variants = Array.isArray(item.variants) && item.variants.length
                ? `<div class="m-mini-tags">${item.variants.slice(0, 3).map(v => `<span>${escapeHtml(typeof v === 'string' ? v : (v.name || 'Variant'))}</span>`).join('')}</div>`
                : '';
            return `<article class="menu-card ${available ? '' : 'is-disabled'}">
                <div class="menu-card-media">${media}${featured}</div>
                <div class="menu-card-body">
                    <div class="menu-card-top">
                        <div class="m-menu-tags"><span class="m-category-label">${escapeHtml(catName)}</span><span class="m-food-type is-${foodType}">${foodLabel}</span></div>
                        <span class="menu-card-price">${money(item.price)}</span>
                    </div>
                    <div class="menu-card-name">${escapeHtml(item.name)}</div>
                    <div class="menu-card-desc">${escapeHtml(item.description || '')}</div>
                    ${variants}
                    <div class="menu-card-footer">
                        ${action}
                    </div>
                </div>
            </article>`;
        }).join('');

        $('#m-items-grid').html(`
            <div class="m-menu-section-head">
                <div>
                    <span>Now serving</span>
                    <h2>${escapeHtml(activeLabel)}</h2>
                </div>
                <strong>${items.length} item${items.length === 1 ? '' : 's'}</strong>
            </div>
            ${cards}
        `);
    }

    function syncCartCount(){
        const total = state.cart.reduce((sum, entry) => sum + Number(entry.qty || 0), 0);
        $('#m-cart-count, #m-cart-count-drawer').text(total);
        $('.cart-fab').toggleClass('has-items', total > 0);
        updateStickyCartBar();
    }

    function updateQty(itemId, delta){
        const item = state.items.find(entry => Number(entry.id) === Number(itemId));
        if(!item){ return; }
        let existing = state.cart.find(entry => Number(entry.item_id) === Number(itemId));
        if(!existing){
            existing = {
                item_id: Number(item.id),
                name: item.name,
                price: Number(item.price),
                qty: 0,
                emoji: item.emoji || '🍽️',
                image: item.image || '',
                variants: [],
                addons: []
            };
            state.cart.push(existing);
        }
        existing.qty += delta;
        if(existing.qty <= 0){
            state.cart = state.cart.filter(entry => Number(entry.item_id) !== Number(itemId));
        }
        syncCartCount();
        const activeCat = Number($('#m-cat-strip .cat-pill.active').data('category-id') || 0);
        renderItems(activeCat);
        renderCart();
        renderCheckout();
        updateStickyCartBar();
    }

    function renderCart(){
        const subtotal = state.cart.reduce((sum, entry) => sum + (Number(entry.price) * Number(entry.qty)), 0);
        const tax = subtotal * 0.05;
        const service = 0;
        const total = subtotal + tax + service;
        $('#cart-list').html(state.cart.length ? state.cart.map(entry => {
            const media = entry.image
                ? `<img src="${escapeHtml(entry.image)}" alt="${escapeHtml(entry.name)}" class="cart-item-img" loading="lazy">`
                : `<div class="cart-item-emoji">${entry.emoji || '🍽️'}</div>`;
            return `<div class="cart-item">
                ${media}
                <div class="cart-item-name">${escapeHtml(entry.name)} × ${Number(entry.qty)}</div>
                <div class="qty-ctrl">
                    <button class="qty-btn" type="button" data-qty="minus" data-item-id="${entry.item_id}">−</button>
                    <span class="qty-num">${entry.qty}</span>
                    <button class="qty-btn" type="button" data-qty="plus" data-item-id="${entry.item_id}">+</button>
                </div>
                <div class="cart-item-price">${money(Number(entry.price) * Number(entry.qty))}</div>
            </div>`;
        }).join('') : '<div class="section-card">Your cart is empty.</div>');
        $('#cart-sub').text(money(subtotal));
        $('#cart-tax').text(money(tax));
        $('#cart-service').text(money(service));
        $('#cart-total').text(money(total));
    }

    function getPaymentOptions(){
        if(!state.payment){ return []; }
        const options = [];
        if(Number(state.payment.cash_enabled) === 1){
            options.push({id: 'cash', icon: '💵', title: 'Cash', desc: 'Pay at table or counter'});
        }
        if(Number(state.payment.upi_enabled) === 1){
            const upiDesc = state.payment.upi_id ? ('Pay to ' + state.payment.upi_id) : 'UPI enabled; ask restaurant for UPI ID';
            options.push({id: 'upi', icon: '📲', title: 'UPI', desc: upiDesc});
        }
        if(Number(state.payment.online_enabled || 0) === 1 || (state.payment.gateway && (Number(state.payment.gateway.razorpay_enabled) === 1 || Number(state.payment.gateway.phonepe_enabled) === 1)) || state.payment.razorpay_key || state.payment.stripe_publishable_key){
            const gatewayData = state.payment.gateway || {};
            const providers = gatewayData.providers || [];
            const gateway = providers.length ? providers.map(name => name === 'razorpay' ? 'Razorpay' : (name === 'phonepe' ? 'PhonePe' : name)).join(' / ') : (state.payment.razorpay_key ? 'Razorpay' : 'Online');
            options.push({id: 'online', icon: '💳', title: 'Online Gateway', desc: gateway + ' auto paid/unpaid'});
        }
        return options;
    }

    function ensureSelectedPayment(){
        const options = getPaymentOptions();
        if(!options.length){
            state.selectedPay = '';
            return;
        }
        if(!options.some(option => option.id === state.selectedPay)){
            state.selectedPay = options[0].id;
        }
    }


    function normalizeUpiId(value){
        return String(value || '').trim().replace(/\s+/g, '').toLowerCase();
    }

    function isValidUpiId(value){
        return /^[a-z0-9.\-_]{2,256}@[a-z][a-z0-9.\-_]{2,64}$/i.test(normalizeUpiId(value));
    }

    function buildUpiPaymentUri(total){
        if(!state.payment || !state.payment.upi_id){ return ''; }
        const payee = normalizeUpiId(state.payment.upi_id);
        const amount = Math.max(1, Number(total || 0));
        if(!isValidUpiId(payee) || !Number.isFinite(amount)){ return ''; }

        /*
         * Keep the UPI URI intentionally minimal.
         * Some UPI apps decline payments when pn/tr are sent but do not exactly
         * match the bank-registered VPA/merchant metadata.
         */
        const params = new URLSearchParams({
            pa: payee,
            am: amount.toFixed(2),
            cu: 'INR',
            tn: 'FluuexQR order'
        });
        return 'upi://pay?' + params.toString();
    }

    function upiAppsHtml(total){
        const upiUri = buildUpiPaymentUri(total);
        const disabled = upiUri ? '' : ' disabled';
        const apps = [
            {id:'phonepe', name:'PhonePe', icon:'🟣'},
            {id:'gpay', name:'Google Pay', icon:'🔵'},
            {id:'paytm', name:'Paytm', icon:'🔷'},
            {id:'bhim', name:'BHIM', icon:'🇮🇳'},
            {id:'other', name:'Other UPI App', icon:'📲'}
        ];
        return `<div class="upi-pay-panel" id="menuqr-upi-pay-panel">
            <div class="upi-pay-head">
                <div>
                    <div class="upi-pay-kicker">Pay securely with UPI</div>
                    <h4>Pay ${money(total)}</h4>
                    <p>Choose any UPI app. The app will use the bank-registered receiver name for this UPI ID.</p>
                </div>
                <div class="upi-pay-amount">${money(total)}</div>
            </div>
            <button class="btn btn-success btn-full btn-lg upi-main-pay" type="button" id="menuqr-upi-pay-now"${disabled}>
                Pay ${money(total)} Now
            </button>
            <div class="upi-app-grid">
                ${apps.map(app => `<button type="button" class="upi-app-btn" data-upi-app="${app.id}"${disabled}>
                    <span>${app.icon}</span><strong>${app.name}</strong>
                </button>`).join('')}
            </div>
            <div class="upi-help-row">
                <button type="button" class="btn btn-outline btn-sm" id="menuqr-copy-upi">Copy UPI ID</button>
                <span id="menuqr-upi-copy-status"></span>
            </div>
            <div class="upi-after-pay ${state.upiPaymentAttempted ? 'is-visible' : ''}" id="menuqr-upi-after-pay">
                <strong>After payment</strong>
                <p>Return here, enter UTR/reference if available, then place order.</p>
            </div>
        </div>`;
    }

    function paymentOptionsHtml(){
        if(!state.payment){ return '<div class="alert alert-warning">No payment methods configured.</div>'; }
        ensureSelectedPayment();
        const options = getPaymentOptions();
        if(!options.length){
            return '<div class="alert alert-warning">No payment methods enabled. Please ask the restaurant to enable payment settings.</div>';
        }
        return options.map(option => `<div class="pay-option ${state.selectedPay===option.id?'selected':''}" data-payment-method="${option.id}">
            <div class="pay-option-icon">${option.icon}</div>
            <div class="pay-option-info"><h4>${option.title}</h4><p>${option.desc}</p></div>
        </div>`).join('');
    }

    function paymentDetailHtml(total){
        if(state.selectedPay === 'upi' && state.payment){
            const screenshot = Number(state.payment.screenshot_enabled) === 1
                ? `<div class="form-group" style="margin-top:12px;"><label class="form-label">Payment Screenshot / Proof</label><input class="form-input" id="upi-screenshot" type="file" accept="image/*"></div>`
                : '';
            const upiId = state.payment.upi_id || '';
            const qr = state.payment.upi_qr ? `<div class="upi-qr-wrap"><img src="${escapeHtml(state.payment.upi_qr)}" alt="UPI QR"></div>` : '';
            const upiBlock = upiId
                ? `<div class="upi-id-card"><span>Restaurant UPI ID</span><strong>${escapeHtml(upiId)}</strong></div>`
                : `<div class="alert alert-warning">UPI is enabled, but UPI ID is not configured. Please confirm payment details with restaurant staff.</div>`;
            return `<div class="upi-checkout-card">
                ${upiBlock}
                ${qr}
                ${upiAppsHtml(total)}
                <div class="form-group" style="margin-top:12px;">
                    <label class="form-label">UTR / Reference Number</label>
                    <input class="form-input" id="upi-ref" placeholder="Enter UTR / Ref after payment">
                </div>
                ${screenshot}
                <div class="alert alert-info" style="margin-top:12px;">UPI payments are paid directly to the restaurant. Restaurant staff can verify the reference/screenshot.</div>
            </div>`;
        }
        if(state.selectedPay === 'online'){
            const gatewayData = state.payment && state.payment.gateway ? state.payment.gateway : {};
            const providers = gatewayData.providers || [];
            const gatewayName = providers.length ? providers.map(name => name === 'razorpay' ? 'Razorpay' : (name === 'phonepe' ? 'PhonePe' : name)).join(' / ') : 'Online Gateway';
            const providerButtons = providers.length > 1
                ? `<div class="gateway-provider-row">${providers.map(provider => `<button type="button" class="gateway-provider-btn ${provider === (gatewayData.provider || 'razorpay') ? 'selected' : ''}" data-gateway-provider="${provider}">${provider === 'razorpay' ? 'Razorpay' : 'PhonePe'}</button>`).join('')}</div>`
                : '';
            return `<div class="section-card online-pay-card">
                <div class="online-pay-icon">💳</div>
                <h4>Pay Online</h4>
                <p>${escapeHtml(gatewayName)} will confirm the payment automatically. Paid orders show as <strong>paid</strong>; cancelled/failed payments remain <strong>unpaid</strong>.</p>
                ${providerButtons}
                <div class="sum-row sum-total"><span>Amount</span><span>${money(total)}</span></div>
                <div id="menuqr-online-status" class="alert alert-info" style="margin-top:12px;">Tap Place Order to open the secure gateway checkout.</div>
            </div>`;
        }
        return `<div class="section-card cash-pay-card">
            <div class="online-pay-icon">💵</div>
            <h4>Cash Payment</h4>
            <p>Pay ${money(total)} at your table or billing counter.</p>
        </div>`;
    }

    function renderCheckout(){
        const subtotal = state.cart.reduce((sum, entry) => sum + (Number(entry.price) * Number(entry.qty)), 0);
        const discount = state.coupon ? Number(state.coupon.discount || 0) : 0;
        const taxable = Math.max(0, subtotal - discount);
        const tax = taxable * 0.05;
        const total = taxable + tax;
        const couponLine = state.coupon ? `<div class="sum-row text-success"><span>Coupon ${escapeHtml(state.coupon.code)}</span><span>−${money(discount)}</span></div>` : '';
        $('#co-summary').html(state.cart.map(entry => `<div class="sum-row"><span>${entry.emoji || '🍽️'} ${entry.name} ×${entry.qty}</span><span>${money(Number(entry.price) * Number(entry.qty))}</span></div>`).join('') + couponLine + `<div class="sum-row"><span>Tax (5%)</span><span>${money(tax)}</span></div>`);
        if(!$('#menuqr-coupon-box').length){
            $('#co-summary').after(`<div class="menuqr-coupon-box" id="menuqr-coupon-box"><div class="form-row"><input class="form-input" id="menuqr-coupon-code" placeholder="Coupon code"><button class="btn btn-outline" type="button" id="menuqr-apply-coupon">Apply</button></div><div class="card-sub" id="menuqr-coupon-msg">Premium/Yearly restaurants can offer coupons.</div></div>`);
        }
        $('#co-total').text(money(total));
        ensureSelectedPayment();
        $('#co-pay-opts').html(paymentOptionsHtml());
        $('#co-pay-detail').html(paymentDetailHtml(total));
        restoreCustomerDetails();
    }


    function applyCoupon(){
        const code = String($('#menuqr-coupon-code').val() || '').trim().toUpperCase();
        if(!code){ $('#menuqr-coupon-msg').text('Enter a coupon code.'); return; }
        const subtotal = state.cart.reduce((sum, entry) => sum + (Number(entry.price) * Number(entry.qty)), 0);
        const restaurantId = Number($('#menuqr-customer-app').data('restaurant-id') || 0);
        $('#menuqr-coupon-msg').text('Checking coupon…');
        $.post(menuqr_ajax.ajax_url, {
            action: 'menuqr_apply_coupon',
            nonce: menuqr_ajax.nonce,
            restaurant_id: restaurantId,
            coupon_code: code,
            subtotal: subtotal,
            _t: Date.now()
        }).done(function(response){
            if(response && response.success){
                state.coupon = response.data;
                $('#menuqr-coupon-msg').text(response.data.message || 'Coupon applied.');
                renderCheckout();
            }else{
                state.coupon = null;
                $('#menuqr-coupon-msg').text((response && response.data && response.data.message) || 'Coupon invalid.');
                renderCheckout();
            }
        }).fail(function(xhr){
            state.coupon = null;
            $('#menuqr-coupon-msg').text((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Coupon invalid.');
            renderCheckout();
        });
    }

    function renderOrderStatus(order){
        const orderStatus = String(order.order_status || 'pending');
        const isCancellable = ['pending','accepted'].indexOf(orderStatus) !== -1;
        const steps = ['pending','accepted','preparing','ready','served'];
        const activeIndex = Math.max(0, steps.indexOf(orderStatus));
        $('#st-current').text(order.order_status.charAt(0).toUpperCase() + order.order_status.slice(1));
        $('#st-items').html((order.items || []).map(entry => `<div class="sum-row"><span>${entry.emoji || '🍽️'} ${entry.name} × ${entry.qty}</span><span>${money(Number(entry.price) * Number(entry.qty))}</span></div>`).join(''));
        const billButton = order.bill_url ? `<div class="mq-actions-center" style="margin-top:12px;"><a class="btn btn-primary btn-full" target="_blank" href="${escapeHtml(order.bill_url)}">🧾 Open / Print Total Bill (${money(order.bill_total || order.final_total || 0)})</a></div>` : '';
        const cancelUi = isCancellable
            ? `<div class="fqx-v207-cancel-card"><strong>Need to cancel?</strong><p>Cancellation is available only before preparation starts.</p><button type="button" class="btn btn-outline fqx-v207-cancel-btn" data-fqx-cancel-order="${escapeHtml(order.id)}">Cancel Order</button><div class="fqx-v207-cancel-note">After <strong>Preparing</strong>, this order cannot be cancelled.</div></div>`
            : ((orderStatus === 'preparing' || orderStatus === 'ready') ? `<div class="fqx-v207-cancel-locked">This order cannot be cancelled because preparation has started.</div>` : '')
            + (orderStatus === 'cancelled' ? `<div class="fqx-v207-cancelled-state"><strong>Order Cancelled</strong><span>The cancellation has been recorded.</span></div>` : '');
        const orderReview = (order.review && order.review.enabled && order.review.show_after_served && order.review_url && order.order_status === 'served')
            ? `<div class="menuqr-review-card served-review"><strong>⭐ How was your experience?</strong><p>${escapeHtml(order.review.message || 'Your honest Google review helps us improve.')}</p><a class="menuqr-review-btn btn-full" target="_blank" rel="noopener" href="${escapeHtml(order.review_url)}">⭐ ${escapeHtml(order.review.button_text || 'Review us on Google')}</a></div>`
            : '';
        $('#st-details').html(`
            <div class="sum-row"><span>Order</span><span>${order.unique_code || ('MQR-' + order.id)}</span></div>
            <div class="sum-row"><span>Payment</span><span>${order.payment_method} / ${order.payment_status}</span></div>
            <div class="sum-row"><span>Bill Status</span><span>${escapeHtml(order.bill_payment_status || order.payment_status || 'unpaid')}</span></div>
            <div class="sum-row"><span>Order Total</span><span>${money(order.final_total)}</span></div>
            <div class="sum-row"><span>4-Hour Bill Total</span><span>${money(order.bill_total || order.final_total)}</span></div>
            ${billButton}
            ${cancelUi}
            ${orderReview}
        `);
        $('#st-steps').html(steps.map((step, index) => {
            const cls = index < activeIndex ? 'done' : (index === activeIndex ? 'active' : '');
            const connector = index < steps.length - 1 ? `<div class="step-connector ${index < activeIndex ? 'done' : ''}"></div>` : '';
            return `<div class="step-block"><div class="step-circle ${cls}">${index + 1}</div><div class="step-label">${step}</div>${connector}</div>`;
        }).join(''));
    }

    $(document).off('click.fqxV207Cancel','[data-fqx-cancel-order]').on('click.fqxV207Cancel','[data-fqx-cancel-order]',function(){
        const button=this, orderId=String(button.getAttribute('data-fqx-cancel-order')||'');
        if(!orderId||button.disabled)return;
        const value=window.prompt('Cancellation reason:\n\n1. Ordered by mistake\n2. Need to change items\n3. Long waiting time\n4. Other\n\nEnter a number or your reason:');
        if(value===null)return;
        const reasons={'1':'Ordered by mistake','2':'Need to change items','3':'Long waiting time'};
        const reason=reasons[String(value).trim()]||String(value).trim();
        if(!reason){window.alert('Please enter a cancellation reason.');return;}
        if(!window.confirm('Cancel this order?\n\nReason: '+reason))return;
        button.disabled=true;button.textContent='Cancelling...';
        $.ajax({url:menuqr_ajax.ajax_url,method:'POST',dataType:'json',data:{action:'fqx_v207_cancel_customer_order',nonce:menuqr_ajax.nonce,order_id:orderId,reason:reason,_t:Date.now()}})
        .done(function(r){if(r&&r.success){window.alert((r.data&&r.data.message)||'Order cancelled.');refreshOrderStatus();return;}window.alert((r&&r.data&&r.data.message)||'This order cannot be cancelled.');button.disabled=false;button.textContent='Cancel Order';})
        .fail(function(xhr){const m=xhr.responseJSON&&xhr.responseJSON.data&&xhr.responseJSON.data.message?xhr.responseJSON.data.message:'This order cannot be cancelled. Please refresh and try again.';window.alert(m);button.disabled=false;button.textContent='Cancel Order';});
    });
    function loadMenu(){
        const root = $('#menuqr-customer-app');
        if(!root.length){ return; }

        const service = getServiceContext();
        const restaurantId = service.restaurantId;
        const tableId = service.tableId;
        const roomId = service.roomId;
        const serviceId = service.serviceId;

        if(!restaurantId){
            $('#m-items-grid').html('<div class="m-empty-menu"><div class="m-empty-icon">🍽️</div><h3>QR not linked</h3><p>This QR code is missing its restaurant mapping.</p></div>');
            return;
        }

        $.ajax({
            url: menuqr_ajax.ajax_url,
            method: 'POST',
            cache: false,
            data: {
                action: 'menuqr_get_menu',
                nonce: menuqr_ajax.nonce,
                restaurant_id: restaurantId,
                table_id: tableId,
                room_id: roomId,
                source: service.orderSource,
                table_number: String($('#menuqr-customer-app').attr('data-table-label') || ''),
                room_number: String($('#menuqr-customer-app').attr('data-room-label') || ''),
                _: Date.now()
            }
        }).done(function(response){
            if(!response || !response.success){
                $('#m-items-grid').html('<div class="m-empty-menu"><div class="m-empty-icon">🍽️</div><h3>Menu not available</h3><p>This restaurant has not added menu items yet, or this QR code is not linked correctly.</p></div>');
                return;
            }
            const payload = response.data || {};
            if(!payload.restaurant){
                $('#m-items-grid').html('<div class="m-empty-menu"><div class="m-empty-icon">🍽️</div><h3>Restaurant not found</h3><p>Please scan a valid QR linked to a restaurant menu.</p></div>');
                return;
            }
            applyMenuPayload(payload);
        }).fail(function(){
            $('#m-items-grid').html('<div class="m-empty-menu"><div class="m-empty-icon">🍽️</div><h3>Unable to load menu</h3><p>Please try again in a moment.</p></div>');
        });
    }


    function getSelectedGatewayProvider(){
        const gateway = state.payment && state.payment.gateway ? state.payment.gateway : {};
        const selectedButton = $('.gateway-provider-btn.selected').data('gateway-provider');
        if(selectedButton){ return String(selectedButton); }
        if(gateway.provider){ return String(gateway.provider); }
        if(gateway.providers && gateway.providers.length){ return String(gateway.providers[0]); }
        return 'razorpay';
    }

    function loadExternalScript(src){
        return new Promise(function(resolve, reject){
            const existing = document.querySelector('script[src="' + src + '"]');
            if(existing){
                if(existing.dataset.loaded === '1'){ resolve(); return; }
                existing.addEventListener('load', resolve, {once:true});
                existing.addEventListener('error', reject, {once:true});
                return;
            }
            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.dataset.loaded = '0';
            script.onload = function(){ script.dataset.loaded = '1'; resolve(); };
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    function setOnlineStatus(message, type){
        const cls = type === 'success' ? 'alert-success' : (type === 'danger' ? 'alert-danger' : 'alert-info');
        $('#menuqr-online-status').removeClass('alert-info alert-success alert-danger').addClass(cls).html(message);
    }

    function finishSuccessfulOrder(orderId){
        state.lastOrderId = Number(orderId);
        state.cart = [];
        syncCartCount();
        renderCart();
        renderCheckout();
        setActiveView('status');
        refreshOrderStatus();
    }

    function markGatewayUnpaid(orderId){
        if(!orderId){ return; }
        $.post(menuqr_ajax.ajax_url, {
            action: 'menuqr_mark_gateway_unpaid',
            nonce: menuqr_ajax.nonce,
            order_id: orderId
        });
    }

    function startGatewayOrder(){
        if(!state.cart.length){ return; }
        const service = getServiceContext();
        const restaurantId = service.restaurantId;
        const tableId = service.tableId;
        const roomId = service.roomId;
        const provider = getSelectedGatewayProvider();
        setOnlineStatus('Creating secure payment order…', 'info');

        $.ajax({
            url: menuqr_ajax.ajax_url,
            type: 'POST',
            dataType: 'json',
            cache: false,
            data: {
                action: 'menuqr_create_gateway_order',
                nonce: menuqr_ajax.nonce,
                restaurant_id: restaurantId,
                table_id: tableId,
                room_id: roomId,
                order_source: service.orderSource,
                items: JSON.stringify(state.cart),
                customer_note: $('#co-note').val() || '',
                bill_session_token: ensureBillSessionToken(),
                customer_name: $('#co-customer-name').val() || '',
                customer_whatsapp: $('#co-customer-whatsapp').val() || '',
                provider: provider,
                coupon_code: state.coupon ? state.coupon.code : '',
                _t: Date.now()
            }
        }).done(function(response){
            if(!response || !response.success){
                setOnlineStatus(escapeHtml((response && response.data && response.data.message) || 'Gateway order failed.'), 'danger');
                return;
            }

            const data = response.data || {};
            state.lastOrderId = Number(data.order_id || 0);

            if(data.provider === 'razorpay'){
                setOnlineStatus('Opening Razorpay checkout…', 'info');
                loadExternalScript('https://checkout.razorpay.com/v1/checkout.js').then(function(){
                    const checkout = new Razorpay({
                        key: data.key,
                        amount: data.amount,
                        currency: data.currency || 'INR',
                        name: data.name || 'FluuexQR',
                        description: data.description || 'FluuexQR Order',
                        order_id: data.razorpay_order_id,
                        handler: function(result){
                            setOnlineStatus('Verifying payment…', 'info');
                            $.post(menuqr_ajax.ajax_url, {
                                action: 'menuqr_verify_razorpay_payment',
                                nonce: menuqr_ajax.nonce,
                                order_id: data.order_id,
                                razorpay_order_id: result.razorpay_order_id,
                                razorpay_payment_id: result.razorpay_payment_id,
                                razorpay_signature: result.razorpay_signature
                            }).done(function(verifyResponse){
                                if(verifyResponse && verifyResponse.success){
                                    setOnlineStatus('Payment successful. Order marked paid.', 'success');
                                    finishSuccessfulOrder(data.order_id);
                                }else{
                                    markGatewayUnpaid(data.order_id);
                                    setOnlineStatus(escapeHtml((verifyResponse && verifyResponse.data && verifyResponse.data.message) || 'Payment verification failed. Order marked unpaid.'), 'danger');
                                }
                            }).fail(function(xhr){
                                markGatewayUnpaid(data.order_id);
                                setOnlineStatus(escapeHtml((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Payment verification failed. Order marked unpaid.'), 'danger');
                            });
                        },
                        modal: {
                            ondismiss: function(){
                                markGatewayUnpaid(data.order_id);
                                setOnlineStatus('Payment cancelled. Order remains unpaid.', 'danger');
                            }
                        },
                        theme: { color: '#e94560' }
                    });
                    checkout.open();
                }).catch(function(){
                    markGatewayUnpaid(data.order_id);
                    setOnlineStatus('Could not load Razorpay checkout. Order remains unpaid.', 'danger');
                });
                return;
            }

            if(data.provider === 'phonepe'){
                setOnlineStatus('PhonePe gateway settings are saved. Complete PhonePe production redirect/callback setup to auto-confirm payments. Order remains pending/unpaid until gateway callback verifies it.', 'info');
                return;
            }

            setOnlineStatus('Unsupported gateway provider.', 'danger');
        }).fail(function(xhr){
            setOnlineStatus(escapeHtml((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Gateway order failed.'), 'danger');
        });
    }

    function placeOrder(){
        if(!state.cart.length){ return; }
        if(state.selectedPay === 'online'){
            startGatewayOrder();
            return;
        }
        if(state.selectedPay === 'upi'){
            const refValue = String($('#upi-ref').val() || '').trim();
            if(!state.upiPaymentAttempted && !refValue){
                $('#menuqr-upi-pay-panel').addClass('needs-attention');
                setTimeout(function(){ $('#menuqr-upi-pay-panel').removeClass('needs-attention'); }, 1200);
                alert('Please tap Pay Now or choose a UPI app before placing the order.');
                return;
            }
            if(!refValue){
                $('#upi-ref').val('UPI-' + Date.now());
            }
        }
        const service = getServiceContext();
        const restaurantId = service.restaurantId;
        const tableId = service.tableId;
        const roomId = service.roomId;
        const orderSource = service.orderSource;
        const formData = new window.FormData();
        formData.append('action', 'menuqr_place_order');
        formData.append('nonce', menuqr_ajax.nonce);
        formData.append('restaurant_id', restaurantId);
        formData.append('table_id', tableId);
        formData.append('room_id', roomId);
        formData.append('order_source', orderSource);
        formData.append('items', JSON.stringify(state.cart));
        formData.append('payment_method', state.selectedPay);
        formData.append('payment_reference', $('#upi-ref').val() || '');
        formData.append('customer_note', $('#co-note').val() || '');
        formData.append('bill_session_token', ensureBillSessionToken());
        formData.append('customer_name', $('#co-customer-name').val() || '');
        formData.append('customer_whatsapp', $('#co-customer-whatsapp').val() || '');
        formData.append('coupon_code', state.coupon ? state.coupon.code : '');
        persistCustomerDetails();
        const screenshotInput = document.getElementById('upi-screenshot');
        if(screenshotInput && screenshotInput.files && screenshotInput.files[0]){
            formData.append('payment_screenshot', screenshotInput.files[0]);
        }
        $.ajax({
            url: menuqr_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false
        }).done(function(response){
            if(!response.success){ alert((response.data && response.data.message) || 'Order failed.'); return; }
            state.lastOrderId = Number(response.data.order_id);
            state.cart = [];
            state.coupon = null;
            syncCartCount();
            renderCart();
            renderCheckout();
            setActiveView('status');
            refreshOrderStatus();
            setTimeout(function(){ if($('#menuqr-bill-history').is(':visible')){ loadBillHistory(); } }, 700);
        }).fail(function(xhr){
            alert((xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Order failed');
        });
    }

    function refreshOrderStatus(){
        if(!state.lastOrderId){ return; }
        $.get(menuqr_ajax.ajax_url, {
            action: 'menuqr_get_order_status',
            nonce: menuqr_ajax.nonce,
            order_id: state.lastOrderId
        }).done(function(response){
            if(response.success){
                renderOrderStatus(response.data.order);
            }
        });
    }


    function unlockKitchenAudio(){
        if(state.kitchenAudioUnlocked){ return; }
        state.kitchenAudioUnlocked = true;
    }

    function playKitchenPing(){
        try {
            const AudioContextClass = window.AudioContext || window.webkitAudioContext;
            if(!AudioContextClass){ return; }
            const ctx = new AudioContextClass();
            const oscillator = ctx.createOscillator();
            const gain = ctx.createGain();
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, ctx.currentTime);
            gain.gain.setValueAtTime(0.001, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.12, ctx.currentTime + 0.01);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.25);
            oscillator.connect(gain);
            gain.connect(ctx.destination);
            oscillator.start();
            oscillator.stop(ctx.currentTime + 0.26);
        } catch(err) {
            // ignore audio restrictions
        }
    }

    function orderAgeMinutes(order){
        if(!order || !order.created_at){ return 0; }
        const parsed = new Date(String(order.created_at).replace(' ', 'T'));
        if(Number.isNaN(parsed.getTime())){ return 0; }
        return Math.max(0, Math.floor((Date.now() - parsed.getTime()) / 60000));
    }

    function kitchenSourceMeta(order){
        const source = String(order.order_source || ((Number(order.room_id || 0) > 0 || order.room_number) ? 'room_qr' : 'table_qr')).toLowerCase();
        const isRoom = source === 'room_qr' || Number(order.room_id || 0) > 0 || !!order.room_number;
        const label = isRoom ? 'Room' : 'Table';
        const value = isRoom ? (order.room_number || order.room_id || '?') : (order.table_number || order.table_id || '?');
        return { source, isRoom, label, value };
    }

    function kitchenTimerClass(minutes){
        if(minutes >= 20){ return 'urgent'; }
        if(minutes >= 10){ return 'warn'; }
        return 'ok';
    }

    function kitchenStatusLabel(status){
        const map = {
            pending: 'Pending',
            accepted: 'Accept',
            preparing: 'Preparing',
            ready: 'Ready',
            served: 'Served'
        };
        return map[status] || status;
    }

    function kitchenMarkup(order){
        const minutes = orderAgeMinutes(order);
        const isNew = state.kitchenKnownIds.indexOf(Number(order.id)) === -1 && state.kitchenKnownIds.length > 0;
        const firstItem = (order.items || [])[0] || {};
        const hero = firstItem.image
            ? `<img src="${firstItem.image}" alt="${escapeHtml(firstItem.name || 'Order item')}" class="kc-thumb" />`
            : `<div class="kc-thumb kc-thumb-emoji">${escapeHtml(firstItem.emoji || '🍽️')}</div>`;
        const actions = [
            {status: 'accepted', label: 'Accept'},
            {status: 'preparing', label: 'Preparing'},
            {status: 'ready', label: 'Ready'},
            {status: 'served', label: 'Served'}
        ].map(entry => {
            const extra = entry.status === 'served' ? ' btn-kitchen-success' : '';
            return `<button class="btn btn-sm btn-kitchen${extra}" data-kitchen-order="${order.id}" data-status="${entry.status}">${entry.label}</button>`;
        }).join('');
        const payLabel = String(order.payment_method || 'cash').toUpperCase();
        const payStatus = String(order.payment_status || 'pending');
        const sourceMeta = kitchenSourceMeta(order);
        return `<div class="kc ${minutes >= 20 ? 'urgent' : ''} ${isNew ? 'new-order' : ''}">
            <div class="kc-head">
                <div class="kc-head-main">
                    ${hero}
                    <div>
                        <div class="kc-table">${sourceMeta.label} ${escapeHtml(String(sourceMeta.value || '?'))}</div>
                        <div class="kc-source-badge ${sourceMeta.isRoom ? 'is-room' : 'is-table'}">${sourceMeta.isRoom ? 'Room Order' : 'Table Order'}</div>
                        <div class="kc-meta">${order.unique_code || ('MQR-' + order.id)}</div>
                    </div>
                </div>
                <div class="kc-head-side">
                    <div class="kc-timer ${kitchenTimerClass(minutes)}">${minutes}m</div>
                    <span class="badge badge-${order.order_status}">${kitchenStatusLabel(order.order_status)}</span>
                </div>
            </div>
            <div class="kc-pay-row">
                <span class="tag tag-blue">${escapeHtml(payLabel)}</span>
                <span class="tag ${payStatus === 'paid' ? 'tag-green' : 'tag-accent'}">${escapeHtml(payStatus)}</span>
                <span class="kc-total">${money(order.final_total || 0)}</span>
            </div>
            <div class="kc-items">${(order.items || []).map(entry => `<div class="kc-item"><span>${escapeHtml(entry.name || '')}</span><span class="kc-item-qty">${entry.qty}</span></div>`).join('')}</div>
            <div class="kc-actions">${actions}</div>
            ${(order.customer_note ? `<div class="kc-note">Note: ${escapeHtml(order.customer_note)}</div>` : '')}
        </div>`;
    }

    
function refreshKitchen(){
    const root = $('#v-kitchen-app');
    if(!root.length || state.kitchenPollBusy){ return; }
    const restaurantId = Number(root.data('restaurant-id') || 0);
    const restaurantIds = String(root.data('restaurant-ids') || '');
    state.kitchenPollBusy = true;
    state.kitchenLastRequestAt = Date.now();
    $.ajax({
        url: menuqr_ajax.ajax_url,
        method: 'POST',
        cache: false,
        timeout: 15000,
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
        },
        dataType: 'json',
        data: {
            action: 'menuqr_get_kitchen_orders',
            nonce: menuqr_ajax.nonce,
            restaurant_id: restaurantId,
            restaurant_ids: restaurantIds,
            _t: state.kitchenLastRequestAt
        }
    }).done(function(response){
        if(!response || !response.success){
            $('#k-grid').html('<div class="section-card kitchen-empty-card">' + escapeHtml((response && response.data && response.data.message) || 'Kitchen fetch failed.') + '</div>');
            return;
        }
        const orders = response.data.orders || [];
        const ids = orders.map(order => Number(order.id));
        const hasNewOrder = ids.some(id => state.kitchenKnownIds.indexOf(id) === -1) && state.kitchenKnownIds.length > 0;
        $('#k-grid').html(orders.length ? orders.map(kitchenMarkup).join('') : '<div class="section-card kitchen-empty-card">No active kitchen orders yet. Keep this screen open. New orders will appear automatically in 5 seconds.</div>');
        $('#k-live-count').text(String(orders.length));
        if(hasNewOrder){ playKitchenPing(); }
        state.kitchenKnownIds = ids;
        state.kitchenLastRenderedIds = ids;
    }).fail(function(){
        $('#k-grid').append('');
    }).always(function(){
        state.kitchenPollBusy = false;
    });
}

function startKitchenPolling(){
    if(window.MenuQRKitchenInlineActive){ return; }
    const root = $('#v-kitchen-app');
    if(!root.length){ return; }
    if(state.kitchenPollTimer){
        clearInterval(state.kitchenPollTimer);
    }
    refreshKitchen();
    state.kitchenPollTimer = window.setInterval(function(){
        refreshKitchen();
    }, Number(menuqr_ajax.refresh_ms || 5000));

    $(document).off('visibilitychange.menuqrKitchen').on('visibilitychange.menuqrKitchen', function(){
        if(document.visibilityState === 'visible'){
            refreshKitchen();
        }
    });

    $(window).off('focus.menuqrKitchen').on('focus.menuqrKitchen', function(){
        refreshKitchen();
    });
}

    function savePaymentSettings(form){
        const data = $(form).serializeArray().reduce((acc, entry) => {
            acc[entry.name] = entry.value;
            return acc;
        }, {});
        ['cash_enabled','upi_enabled','screenshot_enabled'].forEach(name => {
            data[name] = $(form).find(`[name="${name}"]`).is(':checked') ? 1 : 0;
        });
        data.action = 'menuqr_save_payment_settings';
        data.nonce = menuqr_ajax.nonce;
        $.post(menuqr_ajax.ajax_url, data).done(function(response){
            $('#menuqr-payment-result').text(response.data.message || 'Saved.');
        });
    }

    $(document).on('click', '.cat-pill', function(){
        $('.cat-pill').removeClass('active');
        $(this).addClass('active');
        renderItems(Number($(this).data('category-id') || 0));
    });

    $(document).on('input', '#m-menu-search', function(){
        const activeCat = Number($('#m-cat-strip .cat-pill.active').data('category-id') || 0);
        renderItems(activeCat);
    });

    $(document).on('click', '#menuqr-apply-coupon', applyCoupon);

    $(document).on('click', '[data-menu-clear-filter]', function(){
        $('#m-menu-search').val('');
        $('.cat-pill').removeClass('active');
        $('#m-cat-strip .cat-pill').first().addClass('active');
        renderItems(0);
    });

    $(document).on('click', '[data-add-item]', function(e){
        e.preventDefault();
        e.stopPropagation();
        updateQty(Number($(this).data('add-item')), 1);
        updateStickyCartBar();
    });
    $(document).on('click', '[data-qty]', function(e){
        e.preventDefault();
        e.stopPropagation();
        const delta = $(this).data('qty') === 'plus' ? 1 : -1;
        updateQty(Number($(this).data('item-id')), delta);
    });
    $(document).on('click', '[data-menuqr-go="cart"]', function(e){
        e.preventDefault();
        e.stopPropagation();
        renderCart();
        setActiveView('cart');
    });
    $(document).on('click', '[data-menuqr-go="checkout"]', function(e){
        e.preventDefault();
        e.stopPropagation();
        if(!state.cart.length){ setActiveView('menu'); return; }
        renderCheckout();
        setActiveView('checkout');
    });
    $(document).on('click', '#menuqr-header-track-order', function(e){
        e.preventDefault();
        e.stopPropagation();
        if(state.lastOrderId){
            refreshOrderStatus();
            setActiveView('status');
        } else {
            alert('Order tracking will start after you place an order.');
        }
    });
    $(document).on('click', '[data-menuqr-back="menu"]', function(e){ e.preventDefault(); setActiveView('menu'); });
    $(document).on('click', '[data-menuqr-back="cart"]', function(e){ e.preventDefault(); renderCart(); setActiveView('cart'); });
    $(document).on('click', '#menuqr-place-order', placeOrder);

    $(document).on('click', '#menuqr-upi-pay-now, [data-upi-app]', function(e){
        e.preventDefault();
        const totalText = $('#co-total').text();
        const total = Number(String(totalText).replace(/[^0-9.]/g, '')) || 0;
        const app = $(this).data('upi-app') || 'default';
        const uri = buildUpiPaymentUri(total);
        if(!uri){
            alert('Restaurant UPI ID is missing or invalid. Please update the restaurant UPI ID in Payment Settings.');
            return;
        }
        state.upiPaymentAttempted = true;
        state.upiPaymentApp = app;
        $('#menuqr-upi-after-pay').addClass('is-visible');
        $('#menuqr-upi-pay-panel').addClass('payment-started');
        if(!$('#upi-ref').val()){
            $('#upi-ref').attr('placeholder', 'Enter UTR / Ref, or leave blank to submit as pending');
        }
        window.location.href = uri;
        setTimeout(function(){
            $('#menuqr-upi-after-pay').addClass('is-visible');
        }, 800);
    });

    $(document).on('click', '#menuqr-copy-upi', function(e){
        e.preventDefault();
        const upiId = state.payment && state.payment.upi_id ? state.payment.upi_id : '';
        if(!upiId){ return; }
        const done = function(){
            $('#menuqr-upi-copy-status').text('Copied');
            setTimeout(function(){ $('#menuqr-upi-copy-status').text(''); }, 1500);
        };
        if(navigator.clipboard && navigator.clipboard.writeText){
            navigator.clipboard.writeText(upiId).then(done).catch(function(){});
        } else {
            const input = $('<input>').val(upiId).appendTo('body').select();
            document.execCommand('copy');
            input.remove();
            done();
        }
    });

    $(document).on('click', '[data-gateway-provider]', function(){
        $('.gateway-provider-btn').removeClass('selected');
        $(this).addClass('selected');
    });

    $(document).on('click', '[data-payment-method]', function(){
        const nextPay = $(this).data('payment-method');
        if(state.selectedPay !== nextPay){
            state.upiPaymentAttempted = false;
            state.upiPaymentApp = '';
        }
        state.selectedPay = nextPay;
        renderCheckout();
    });
    $(document).on('click', '#menuqr-status-refresh', refreshOrderStatus);
    $(document).on('submit', '#menuqr-payment-form', function(e){ e.preventDefault(); savePaymentSettings(this); });
    $(document).on('click keydown touchstart', '#v-kitchen-app', unlockKitchenAudio);

    if(!window.MenuQRKitchenInlineActive){
        $(document).on('click', '[data-kitchen-order][data-status]', function(){
            $.post(menuqr_ajax.ajax_url, {
                action: 'menuqr_update_order_status',
                nonce: menuqr_ajax.nonce,
                order_id: $(this).data('kitchen-order'),
                status: $(this).data('status')
            }).done(function(){
                refreshKitchen();
            });
        });
    }


    $(document).on('click', '#menuqr-open-drawer', function(){
        openActionDrawer();
    });
    $(document).on('click', '#menuqr-close-drawer, #menuqr-close-drawer-btn', function(){
        closeActionDrawer();
    });
    $(document).on('click', '#menuqr-mobile-drawer .menuqr-drawer-action[data-menuqr-go]', function(){
        closeActionDrawer();
    });
    $(document).on('keydown', function(event){
        if(event.key === 'Escape'){
            closeActionDrawer();
        }
    });

    $(document).on('click', '#menuqr-view-bill, #menuqr-header-view-bill', function(event){
        event.preventDefault();
        openCustomerBillPage();
    });
    $(document).on('click', '#menuqr-close-bill-history', function(){
        closeBillHistory();
    });
    $(document).on('click', '#menuqr-save-bill-customer', function(){
        saveBillCustomer();
    });
    $(document).on('input', '#co-customer-name,#co-customer-whatsapp', function(){
        persistCustomerDetails();
    });


    $(document).on('submit', '.dashboard-shell form, .mq-form form, form[action*="admin-post.php"]', function(){
        const $form = $(this);
        if(!$form.find('input[name="_menuqr_redirect"]').length){
            $('<input>', {
                type: 'hidden',
                name: '_menuqr_redirect',
                value: window.location.href
            }).appendTo($form);
        }
    });

    $(function(){
        if($('#v-menu').length){
            ensureStickyCartBar();
            ensureBillSessionToken();
            setActiveView('menu');
            loadMenu();
        }
        startKitchenPolling();
        if($('#menuqr-order-status-wrap').length){
            setInterval(refreshOrderStatus, Number(menuqr_ajax.refresh_ms || 5000));
        }
    });
})(jQuery);


/* v45: instant QR template selector feedback */
(function(){
    function initQrTemplateSelector(){
        document.querySelectorAll('.mq-qr-template-form').forEach(function(form){
            form.querySelectorAll('input[name="qr_template"]').forEach(function(input){
                input.addEventListener('change', function(){
                    form.querySelectorAll('.mq-qr-template-option').forEach(function(label){
                        label.classList.remove('is-selected');
                    });
                    const label = input.closest('.mq-qr-template-option');
                    if (label) {
                        label.classList.add('is-selected');
                    }
                    const btn = form.querySelector('button[type="submit"]');
                    if (btn) {
                        btn.textContent = 'Save Selected Template';
                        btn.classList.add('mq-pulse-action');
                    }
                });
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initQrTemplateSelector);
    } else {
        initQrTemplateSelector();
    }
})();


(function($){
    'use strict';

    function setupPublicShell(){
        const toggle = document.querySelector('.fq-mobile-toggle');
        const panel = document.getElementById('fqMobileNav');
        if(toggle && panel){
            toggle.addEventListener('click', function(){
                const isOpen = toggle.getAttribute('aria-expanded') === 'true';
                toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
                panel.hidden = isOpen;
                panel.classList.toggle('is-open', !isOpen);
            });
            panel.querySelectorAll('a').forEach(function(link){
                link.addEventListener('click', function(){
                    toggle.setAttribute('aria-expanded', 'false');
                    panel.hidden = true;
                    panel.classList.remove('is-open');
                });
            });
        }
    }

    function setupCopyBillLink(){
        $(document).on('click', '.menuqr-copy-bill-link', function(){
            const button = this;
            const text = button.getAttribute('data-copy') || '';
            if(!text){ return; }
            navigator.clipboard.writeText(text).then(function(){
                button.classList.add('is-copied');
                button.textContent = 'Copied';
                window.setTimeout(function(){
                    button.classList.remove('is-copied');
                    button.textContent = 'Copy Bill Link';
                }, 1800);
            });
        });
    }

    function setupResponsiveTables(){
        $('.data-table').each(function(){
            const labels = [];
            $(this).find('thead th').each(function(){
                labels.push($(this).text().trim());
            });
            $(this).find('tbody tr').each(function(){
                $(this).find('td').each(function(index){
                    if(!this.getAttribute('data-label') && labels[index]){
                        this.setAttribute('data-label', labels[index]);
                    }
                });
            });
        });
    }

    function setupDashboardSidebar(){
        const shell = document.querySelector('.dashboard-shell');
        const topbar = document.querySelector('.topbar');
        if(!shell || !topbar || document.querySelector('.mq-sidebar-toggle')){ return; }
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'btn btn-outline btn-sm mq-sidebar-toggle';
        button.textContent = 'Menu';
        button.addEventListener('click', function(){
            shell.classList.toggle('sidebar-open');
        });
        topbar.insertBefore(button, topbar.firstChild);
        document.addEventListener('click', function(event){
            if(window.innerWidth > 1199){ return; }
            if(shell.contains(event.target) && !event.target.closest('.sidebar') && !event.target.closest('.mq-sidebar-toggle')){
                shell.classList.remove('sidebar-open');
            }
        });
    }

    $(function(){
        setupPublicShell();
        setupCopyBillLink();
        setupResponsiveTables();
        setupDashboardSidebar();
    });
})(jQuery);


document.addEventListener('DOMContentLoaded', function () {
    const header = document.querySelector('.fq-public-header');
    const mobileToggle = document.querySelector('.fq-mobile-toggle');
    const mobilePanel = document.getElementById('fqMobileNav');

    const syncHeader = function () {
        if (!header) { return; }
        if (window.scrollY > 12) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }
    };

    if (header) {
        syncHeader();
        window.addEventListener('scroll', syncHeader, { passive: true });
    }

    if (mobileToggle && mobilePanel) {
        mobileToggle.addEventListener('click', function () {
            const expanded = mobileToggle.getAttribute('aria-expanded') === 'true';
            mobileToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            if (expanded) {
                mobilePanel.hidden = true;
            } else {
                mobilePanel.hidden = false;
            }
        });

        mobilePanel.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                mobileToggle.setAttribute('aria-expanded', 'false');
                mobilePanel.hidden = true;
            });
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {
    var header = document.querySelector('.fq-public-header');
    var toggle = document.querySelector('.fq-mobile-toggle');
    var panel = document.getElementById('fqMobileNav');
    var overlay = document.querySelector('.fq-mobile-overlay');

    function updateHeaderOffset() {
        if (!header) { return; }
        document.documentElement.style.setProperty('--fq-header-height', header.offsetHeight + 'px');
    }

    function closeMobileNav() {
        if (!toggle || !panel) { return; }
        toggle.setAttribute('aria-expanded', 'false');
        toggle.classList.remove('is-active');
        panel.hidden = true;
        panel.classList.remove('is-open');
        if (overlay) {
            overlay.hidden = true;
            overlay.classList.remove('is-open');
        }
        document.body.classList.remove('fq-mobile-nav-open');
    }

    function openMobileNav() {
        if (!toggle || !panel) { return; }
        toggle.setAttribute('aria-expanded', 'true');
        toggle.classList.add('is-active');
        panel.hidden = false;
        panel.classList.add('is-open');
        if (overlay) {
            overlay.hidden = false;
            overlay.classList.add('is-open');
        }
        document.body.classList.add('fq-mobile-nav-open');
    }

    if (toggle && panel) {
        closeMobileNav();
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            var isOpen = toggle.getAttribute('aria-expanded') === 'true';
            if (isOpen) {
                closeMobileNav();
            } else {
                openMobileNav();
            }
        });
        panel.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeMobileNav);
        });
    }
    if (overlay) {
        overlay.addEventListener('click', closeMobileNav);
    }
    window.addEventListener('resize', function () {
        updateHeaderOffset();
        if (window.innerWidth > 768) {
            closeMobileNav();
        }
    });
    updateHeaderOffset();

    var statNumbers = document.querySelectorAll('.stat-number');
    if (statNumbers.length) {
        var io = new IntersectionObserver(function(entries, observer){
            entries.forEach(function(entry){
                if (!entry.isIntersecting) { return; }
                statNumbers.forEach(function(el){
                    if (el.dataset.done === '1') { return; }
                    var target = parseInt(el.getAttribute('data-target') || '0', 10);
                    var suffix = el.getAttribute('data-suffix') || '';
                    var duration = 1400;
                    var startTime;
                    function tick(ts){
                        if (!startTime) { startTime = ts; }
                        var progress = Math.min((ts - startTime) / duration, 1);
                        var val = Math.floor(progress * target);
                        el.textContent = val + suffix;
                        if (progress < 1) {
                            requestAnimationFrame(tick);
                        } else {
                            el.textContent = target + suffix;
                            el.dataset.done = '1';
                        }
                    }
                    requestAnimationFrame(tick);
                });
                observer.disconnect();
            });
        }, { threshold: 0.35 });
        var targetSection = document.querySelector('.stats-section');
        if (targetSection) {
            io.observe(targetSection);
        }
    }
});
