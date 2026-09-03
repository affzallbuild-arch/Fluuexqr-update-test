<section class="menuqr-checkout-wrap" id="menuqr-checkout-wrap" hidden>
    <div class="back-bar">
        <button class="back-btn" type="button" data-menuqr-back="cart">← Cart</button>
        <h3>Checkout</h3>
    </div>
    <div class="checkout-body">
        <div class="co-section">
            <div class="co-section-title">Order Summary</div>
            <div id="co-summary"></div>
            <div class="divider"></div>
            <div class="sum-row sum-total"><span>Total to Pay</span><span id="co-total">₹0.00</span></div>
        </div>
        <div class="co-section">
            <div class="co-section-title">Customer Details</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="co-customer-name">Name</label>
                    <input class="form-input" id="co-customer-name" placeholder="Customer name">
                </div>
                <div class="form-group">
                    <label class="form-label" for="co-customer-whatsapp">WhatsApp Number</label>
                    <input class="form-input" id="co-customer-whatsapp" inputmode="tel" placeholder="10 digit mobile">
                </div>
            </div>
            <p class="fs-sm text-muted">Same device orders stay in one running bill for 4 hours.</p>
        </div>
        <div class="co-section">
            <div class="co-section-title">Payment Method</div>
            <div id="co-pay-opts"></div>
            <div id="co-pay-detail"></div>
        </div>
        <div class="co-section">
            <div class="co-section-title">Special Instructions</div>
            <textarea class="note-textarea" id="co-note" placeholder="e.g. no onions, less spicy"></textarea>
        </div>
        <button class="btn btn-primary btn-full btn-lg" type="button" id="menuqr-place-order">Place Order 🎉</button>
    </div>
</section>
