/**
 * Created by admin on 13.11.2015.
 */

(function( $ ){
    $('body').on( 'click', '#use_bonus_points', function( event ) {
        $('body').trigger('update_checkout');
    });
})(window.jQuery);
