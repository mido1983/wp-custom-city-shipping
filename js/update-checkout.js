jQuery(document).ready(function ($) {
    function updateCityField() {
        $.ajax({
            type: 'POST',
            url: custom_shipping_params.ajax_url,
            data: {
                action: 'update_city_field'
            },
            success: function (response) {
                if (response.success) {
                    var cities = response.data.cities;
                    var cityField = $('select[name="shipping_city"], select[name="billing_city"], select[name="city"]');
                    cityField.empty();
                    cityField.append('<option value="" unselectable="true">--Выбрать город доставки--</option>');
                    $.each(cities, function (index, city) {
                        cityField.append('<option value="' + city + '">' + city + '</option>');
                    });
                }
            }
        });
    }

    function updateShippingCost() {
        $('body').trigger('update_checkout');
    }

    $(document.body).on('updated_cart_totals', updateCityField);
    $(document).on('change', 'select[name="shipping_city"], select[name="billing_city"], select[name="city"]', updateShippingCost);

    updateCityField();
});
