/**
 * Created by andranik on 10/12/16.
 */

var calculatePrice = function(i)
{
    var singlePrice = $('.single_price_' + i).text();
    var quantity = $('.quantity_' + i).val();
    $('.price_' + i).text(singlePrice * quantity);

    console.log(singlePrice * quantity)
}