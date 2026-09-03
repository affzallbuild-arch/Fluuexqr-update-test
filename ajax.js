
(function($){
    function toDataUrl(svgMarkup){
        return 'data:image/svg+xml;charset=utf-8,' + encodeURIComponent(svgMarkup);
    }

    function svgToPngDownload(svgMarkup, filename){
        const image = new Image();
        const svgData = toDataUrl(svgMarkup);
        image.onload = function(){
            const canvas = document.createElement('canvas');
            canvas.width = image.width || 1200;
            canvas.height = image.height || 1920;
            const context = canvas.getContext('2d');
            context.fillStyle = '#ffffff';
            context.fillRect(0, 0, canvas.width, canvas.height);
            context.drawImage(image, 0, 0);
            const link = document.createElement('a');
            link.download = filename;
            link.href = canvas.toDataURL('image/png');
            link.click();
        };
        image.src = svgData;
    }

    function printQrCard(svgMarkup){
        const printWindow = window.open('', '_blank', 'width=900,height=900');
        if(!printWindow){ return; }
        printWindow.document.write(
            '<!doctype html><html><head><title>Print QR</title><meta name="viewport" content="width=device-width, initial-scale=1">' +
            '<style>body{margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;display:grid;place-items:center;min-height:100vh} .wrap{max-width:420px;width:100%;background:#fff;border-radius:28px;padding:24px;box-shadow:0 16px 50px rgba(15,23,42,.18)} svg{width:100%;height:auto;display:block}@media print{body{background:#fff;padding:0}.wrap{box-shadow:none;padding:0;border-radius:0}}</style>' +
            '</head><body><div class="wrap">' + svgMarkup + '</div><script>window.onload=function(){window.print();};<\/script></body></html>'
        );
        printWindow.document.close();
    }

    function setToast(container, message, type){
        if(!container.length){ return; }
        container.removeClass('is-success is-error is-loading').addClass('is-' + type).text(message).show();
    }

    function updateTemplateSelection(selectedKey){
        $('.mq-qr-template-option').removeClass('is-selected');
        $('.mq-qr-template-option input[value="' + selectedKey + '"]').closest('.mq-qr-template-option').addClass('is-selected');
    }

    function updatePreview(record){
        const preview = $('#menuqr-live-preview');
        preview.html(record.html_markup || '');
        $('#menuqr-preview-url').text(record.qr_url || '');
        $('#menuqr-preview-table').text(record.table_number || '--');
        preview.data('svgMarkup', record.svg_markup || '');
        preview.data('tableNumber', record.table_number || 'table');
        preview.data('printUrl', record.print_url || '');
        preview.data('pngUrl', record.png_download_url || '');
        preview.data('svgUrl', record.svg_download_url || '');
    }

    function currentPayload(){
        return {
            action: '',
            nonce: (window.menuqr_ajax && window.menuqr_ajax.nonce) || '',
            restaurant_id: Number($('#menuqr-qr-builder').data('restaurantId') || 0),
            table_id: Number($('#menuqr-table-id').val() || 0),
            template_key: $('input[name="qr_template"]:checked').val() || 'minimal_clean'
        };
    }

    function requestQr(actionName, onSuccess){
        const payload = currentPayload();
        payload.action = actionName;

        if(!payload.restaurant_id || !payload.table_id){
            setToast($('#menuqr-qr-toast'), 'Please select a restaurant table first.', 'error');
            return;
        }

        setToast($('#menuqr-qr-toast'), 'Processing QR template...', 'loading');

        $.post((window.menuqr_ajax && window.menuqr_ajax.ajax_url) || '', payload)
            .done(function(response){
                if(!response || !response.success || !response.data || !response.data.record){
                    setToast($('#menuqr-qr-toast'), 'Unexpected response from server.', 'error');
                    return;
                }

                updateTemplateSelection(payload.template_key);
                updatePreview(response.data.record);
                setToast($('#menuqr-qr-toast'), response.data.message || 'QR updated successfully.', 'success');
                if(typeof onSuccess === 'function'){ onSuccess(response.data.record); }
            })
            .fail(function(xhr){
                const message = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'QR request failed.';
                setToast($('#menuqr-qr-toast'), message, 'error');
            });
    }

    $(document).on('change', 'input[name="qr_template"], #menuqr-table-id', function(){
        const tableId = Number($('#menuqr-table-id').val() || 0);
        if(!tableId){ return; }
        requestQr((window.menuqr_ajax && window.menuqr_ajax.qr_actions && window.menuqr_ajax.qr_actions.preview) || 'menuqr_preview_qr_template');
    });

    $(document).on('click', '#menuqr-create-qr', function(e){
        e.preventDefault();
        requestQr((window.menuqr_ajax && window.menuqr_ajax.qr_actions && window.menuqr_ajax.qr_actions.create) || 'menuqr_create_qr_template');
    });

    $(document).on('click', '#menuqr-save-template', function(e){
        e.preventDefault();
        requestQr((window.menuqr_ajax && window.menuqr_ajax.qr_actions && window.menuqr_ajax.qr_actions.create) || 'menuqr_create_qr_template');
    });

    $(document).on('click', '#menuqr-bulk-generate', function(e){
        e.preventDefault();
        const payload = currentPayload();
        payload.action = (window.menuqr_ajax && window.menuqr_ajax.qr_actions && window.menuqr_ajax.qr_actions.bulk) || 'menuqr_bulk_generate_qr_templates';
        payload.table_id = 0;
        setToast($('#menuqr-qr-toast'), 'Generating QR templates for all tables...', 'loading');

        $.post((window.menuqr_ajax && window.menuqr_ajax.ajax_url) || '', payload)
            .done(function(response){
                if(response && response.success){
                    setToast($('#menuqr-qr-toast'), (response.data && response.data.message) || 'Bulk generation completed.', 'success');
                } else {
                    setToast($('#menuqr-qr-toast'), 'Bulk generation failed.', 'error');
                }
            })
            .fail(function(xhr){
                const message = (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) || 'Bulk generation failed.';
                setToast($('#menuqr-qr-toast'), message, 'error');
            });
    });

    $(document).on('click', '#menuqr-download-png', function(e){
        e.preventDefault();
        const preview = $('#menuqr-live-preview');
        const svgMarkup = preview.data('svgMarkup');
        if(!svgMarkup){ setToast($('#menuqr-qr-toast'), 'Create the QR first.', 'error'); return; }
        const tableNumber = preview.data('tableNumber') || 'table';
        svgToPngDownload(svgMarkup, 'menuqr-table-' + tableNumber + '.png');
    });

    $(document).on('click', '#menuqr-download-svg', function(e){
        e.preventDefault();
        const preview = $('#menuqr-live-preview');
        const svgUrl = preview.data('svgUrl');
        if(svgUrl){
            window.open(svgUrl, '_blank');
            return;
        }
        const svgMarkup = preview.data('svgMarkup');
        if(!svgMarkup){ setToast($('#menuqr-qr-toast'), 'Create the QR first.', 'error'); return; }
        const blob = new Blob([svgMarkup], {type: 'image/svg+xml;charset=utf-8'});
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'menuqr-template.svg';
        link.click();
        setTimeout(function(){ URL.revokeObjectURL(link.href); }, 1500);
    });

    $(document).on('click', '#menuqr-print-qr', function(e){
        e.preventDefault();
        const preview = $('#menuqr-live-preview');
        const printUrl = preview.data('printUrl');
        if(printUrl){
            window.open(printUrl, '_blank');
            return;
        }
        const svgMarkup = preview.data('svgMarkup');
        if(!svgMarkup){ setToast($('#menuqr-qr-toast'), 'Create the QR first.', 'error'); return; }
        printQrCard(svgMarkup);
    });

    $(document).on('click', '.mq-sidebar-toggle', function(){
        $('body').toggleClass('mq-sidebar-open');
    });

    $(document).on('click', '.mq-sidebar-overlay', function(){
        $('body').removeClass('mq-sidebar-open');
    });

    $(function(){
        const tableId = Number($('#menuqr-table-id').val() || 0);
        if(tableId){
            requestQr((window.menuqr_ajax && window.menuqr_ajax.qr_actions && window.menuqr_ajax.qr_actions.preview) || 'menuqr_preview_qr_template');
        }
    });
})(jQuery);
