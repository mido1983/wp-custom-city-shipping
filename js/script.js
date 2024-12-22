jQuery(document).ready(function($) {

    $('form.checkout').on('change', '#billing_city', function(){
        console.log('City changed');
        $('body').trigger('update_checkout');
    });



        // Добавление новой группы городов
        $('.add-group').on('click', function() {
            var groupIndex = $('.shipping-group').length;
            var groupHtml = '<div class="shipping-group">' +
                '<h3>' + 'Group ' + (groupIndex + 1) + '</h3>' +
                '<div class="form-group">' +
                '<label for="group-cities-' + groupIndex + '">' + 'Cities (comma-separated)' + '</label>' +
                '<input type="text" class="form-control" id="group-cities-' + groupIndex + '" name="custom_city_shipping_groups[' + groupIndex + '][cities]">' +
                '</div>' +
                '<div class="form-group">' +
                '<label for="group-price-' + groupIndex + '">' + 'Shipping Price' + '</label>' +
                '<input type="number" class="form-control" id="group-price-' + groupIndex + '" name="custom_city_shipping_groups[' + groupIndex + '][price]" step="0.01">' +
                '</div>' +
                '<button type="button" class="btn btn-danger remove-group">' + 'Remove Group' + '</button>' +
                '</div>';
            $('#shipping-groups-container').append(groupHtml);
        });

        // Удаление группы городов
        $(document).on('click', '.remove-group', function() {
            $(this).closest('.shipping-group').remove();
        });

    // Добавление новой группы по категориям товаров
    $('.add-category-group').on('click', function() {
        var categoryGroupIndex = $('.category-shipping-group').length;
        var categoryGroupHtml = '<div class="category-shipping-group">' +
            '<h3>' + 'Category Group ' + (categoryGroupIndex + 1) + '</h3>' +
            '<div class="form-group">' +
            '<label for="category-group-category-' + categoryGroupIndex + '">' + 'Product Category' + '</label>' +
            '<select class="form-control" id="category-group-category-' + categoryGroupIndex + '" name="custom_city_shipping_category_groups[' + categoryGroupIndex + '][category]">' +
            '<option value="">' + 'Select a category' + '</option>' +
            '<?php foreach ($categories as $category) : ?>' +
            '<option value="<?php echo esc_attr($category->term_id); ?>"><?php echo esc_html($category->name); ?></option>' +
            '<?php endforeach; ?>' +
            '</select>' +
            '</div>' +
            '<div class="form-group">' +
            '<label for="category-group-cities-' + categoryGroupIndex + '">' + 'Cities (comma-separated)' + '</label>' +
            '<input type="text" class="form-control" id="category-group-cities-' + categoryGroupIndex + '" name="custom_city_shipping_category_groups[' + categoryGroupIndex + '][cities]">' +
            '</div>' +
            '<div class="form-group">' +
            '<label for="category-group-price-' + categoryGroupIndex + '">' + 'Shipping Price' + '</label>' +
            '<input type="number" class="form-control" id="category-group-price-' + categoryGroupIndex + '" name="custom_city_shipping_category_groups[' + categoryGroupIndex + '][price]" step="0.01">' +
            '</div>' +
            '<div class="form-group">' +
            '<div class="form-check">' +
            '<input type="checkbox" class="form-check-input" id="category-group-free-shipping-' + categoryGroupIndex + '" name="custom_city_shipping_category_groups[' + categoryGroupIndex + '][free_shipping]" value="1">' +
            '<label class="form-check-label" for="category-group-free-shipping-' + categoryGroupIndex + '">' + 'Free Shipping' + '</label>' +
            '</div>' +
            '</div>' +
            '<button type="button" class="btn btn-danger remove-category-group">' + 'Remove Group' + '</button>' +
            '</div>';
        $('#category-shipping-groups-container').append(categoryGroupHtml);
    });



        // Удаление группы по категориям товаров
        $(document).on('click', '.remove-category-group', function() {
            $(this).closest('.category-shipping-group').remove();
        });
    });

// jQuery(document).ready(function($) {
//     $(document).on('change', '#billing_city', function() {
//         var city = $(this).val();
//         var deliveryAvailable = $(this).find('option:selected').data('delivery-available');
//
//         if (!deliveryAvailable) {
//             $('.woocommerce-checkout-place-order').hide();
//             alert('Delivery is not available to the selected city.');
//         } else {
//             $('.woocommerce-checkout-place-order').show();
//         }
//     });
// });
