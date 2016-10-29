/**
 * Created by andranik on 10/12/16.
 */

var calculatePrice = function(i)
{
    var singlePrice = $('.single_price_' + i).text();
    var quantity = $('.quantity_' + i).val();
    var discount = $('.discount_' + i).val();
    $('.price_' + i).text(singlePrice * quantity * (100 - discount) / 100);

    calculateTotal();
}

var calculateTotal = function()
{
    var totalPrice = 0;
    $('.price').each(function( i ) {
        totalPrice += parseFloat($(this).text());
    });

    $('.total_price').text(totalPrice);
}