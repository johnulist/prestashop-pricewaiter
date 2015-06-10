{*
* 2014-2015 PriceWaiter LLC
*
* The MIT License (MIT)
*
* Copyright (c) <year> <copyright holders>
*
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
*
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
*
*  @author    PriceWaiter LLC <extensions@pricewaiter.com>
*  @copyright 2014-2015 PriceWaiter LLC
*  @license   http://opensource.org/licenses/MIT
*}

{if $pw_api_key != ''}
<p class="buttons_bottom_block" style="{$pw_custom_css|escape:'html':'UTF-8'}"><span id="pricewaiter"></span></p>

<script type="text/javascript">
var PriceWaiterOptions = {
    enableButton: {$pw_enable_button},
    enableConversionTools: {$pw_enable_conversion_tools},
    product: {
        sku: '{$pw_product->reference}',
        name: '{$pw_product_name|escape:'javascript':'UTF-8'}',
        price: '{$pw_product->price}',
        image: '{$pw_default_image}'
    },
    quantity: '{$pw_product->minimal_quantity}',
    onLoad: function() {

        var pw_combinations = {$pw_combinations};

        var pw_attributes = {$pw_attributes};

        PriceWaiter.platform.onLoad(pw_combinations, pw_attributes, '{$pw_ps_version|escape:'javascript':'UTF-8'}');
    }
};
</script>
<script type="text/javascript" src="{$pw_widget_host}/script/{$pw_api_key}.js" async></script>
{/if}
