/**
 * Created by andranik on 9/27/16.
 */

jQuery(document).ready(function()
{
    var productList = jQuery('#product-fields-list');
    var prototype = productList.attr('data-prototype');
    var productsCount = productList.attr('data-product-count');

    jQuery('#add-product').click(function(e) {
        e.preventDefault();

//        var productList = jQuery('#product-fields-list');
//        var newWidget = productList.attr('data-prototype');

        var newWidget = prototype.replace(/__name__/g, productsCount);
        productsCount++;

        var newLi = jQuery('<li></li>').html(newWidget);
        newLi.appendTo(productList);
    });
})