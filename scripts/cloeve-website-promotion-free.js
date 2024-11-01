/**
 * Cloeve Internal Ad Free JS
 */


function cloevePromoFreeClicked(promo_id, click_url) {

    jQuery.ajax({
        type: 'POST',
        url: '/wp-json/cloeve-tech/website-promo-free/v1/record_click',
        data: {'promo_id': promo_id},
        success: function(response) {
            window.location.href = click_url;
        }, error: function() {
            window.location.href = click_url;
        }
    });

}
