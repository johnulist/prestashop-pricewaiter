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
<script src="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.jquery.min.js" type="text/javascript"></script>
<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/chosen/1.4.2/chosen.min.css">
<script type="text/javascript">
$(document).ready(function() {
    $('select[multiple]').chosen({
        // brackets for this obj MUST be on separate lines or Smarty blows up
        width: "400px"
    });
});
</script>
<style>
.pw-install-group {
    margin-bottom: 10px !important;
}

.pw-button {
    border: 2px solid #4C361E;
    border-radius: 5px;
    background-color: #faf5ef;
    font-size: 20px;
    padding: 8px;
    margin-top: 10px;
    display: inline-block;
}
</style>

<div id="pricewaiter" class="text-center">
    <h1><img src="{$module_dir}/views/img/logo_retina.png" width="200" height="71" alt="PriceWaiter" /></h1>
    {if Configuration::get('PRICEWAITER_API_KEY') == '' }
    <fieldset class="pw-install-group"><legend>Create PriceWaiter Account</legend>
        <p>To use the PriceWaiter service you will need obtain an API Key by signing up for an account.</p>
        <a class="pw-button" href="{$pw_signup_url|escape:'html':'UTF-8'}" target="blank">Sign up here!</a>
        <p>After completing signup, return here and paste your API key to get started!</p>
    </fieldset>
    {else}
    <fieldset class="pw-install-group"><legend>Customize PriceWaiter Button</legend>
        <p>You can easily customize PriceWaiter's button to match your store's look and feel.</p>
        <a class="pw-button" href="{$pw_manage_url|escape:'html':'UTF-8'}/stores/{Configuration::get('PRICEWAITER_API_KEY')}/campaigns" target="blank">Configure</a>
    </fieldset>
    {/if}
</div>
