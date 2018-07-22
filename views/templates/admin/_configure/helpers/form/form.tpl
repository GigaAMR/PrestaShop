{**
* Copyright (c) 2012-2018, mollie-ui b.V.
* All rights reserved.
*
* Redistribution and use in source and binary forms, with or without
* modification, are permitted provided that the following conditions are met:
*
* - Redistributions of source code must retain the above copyright notice,
*    this list of conditions and the following disclaimer.
* - Redistributions in binary form must reproduce the above copyright
*    notice, this list of conditions and the following disclaimer in the
*    documentation and/or other materials provided with the distribution.
*
* THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
* EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
* WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
* DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
* DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
* (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
* SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
* CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
* LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
* OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
* DAMAGE.
*
* @author     mollie-ui b.V. <info@mollie.nl>
* @copyright  mollie-ui b.V.
* @license    Berkeley Software Distribution License (BSD-License 2) http://www.opensource.org/licenses/bsd-license.php
* @category   Mollie
* @package    Mollie
* @link       https://www.mollie.nl
*}
{extends file="helpers/form/form.tpl"}

{block name="input"}
  {if $input.type === 'mollie-logo'}
    <div class="form-group row">
      <div class="col-md-12">
        <img src="{$module_dir|escape:'htmlall':'UTF-8' nofilter}mollie/views/img/mollie_logo.png"
             style="max-width:100%; height:auto"
        >
      </div>
    </div>
  {elseif $input.type === 'mollie-br'}
    <br>
  {elseif $input.type === 'mollie-methods'}
    <section class="module_list" style="max-width: 440px">
      <ul class="list-unstyled sortable">
        {foreach $input.methods as $index => $method}
          <li class="module_list_item draggable"
              draggable="true"
              data-pos="{$index|intval}"
              data-method="{$method['id']|escape:'htmlall':'UTF-8'}"
          >
            <div class="module_col_position dragHandle">
              <span class="positions">{$index + 1|intval}</span>
              <div class="btn-group-vertical">
                <a class="mollie-ui btn btn-primary btn-xs mollie-up">
                  <i class="icon-chevron-up"></i>
                </a>
                <a class="mollie-ui btn btn-primary btn-xs mollie-down">
                  <i class="icon-chevron-down"></i>
                </a>
              </div>
            </div>
            <div class="module_col_icon">
              <img width="57" src="{$method['image']|escape:'htmlall':'UTF-8'}" alt="mollie">
            </div>
            <div class="module_col_infos">
              <div style="display: inline-block">
                      <span class="module_name">
                        {$method['name']|escape:'htmlall':'UTF-8'}
                      </span>
              </div>
              <label class="mollie_switch" style="float: right;width: 60px;height: 24px;right: 20px;top: 5px;">
                <input type="checkbox"
                       value="1"
                       style="width: auto;"
                       {if !empty($method['enabled'])}checked="checked"{/if}
                >
                <span class="mollie_slider"></span>
              </label>
            </div>
          </li>
        {/foreach}
      </ul>
    </section>
    <em class="mollie_desc">{l s='Enable or disable the payment methods. You can drag and drop to rearrange the payment methods.' mod='mollie'}</em>
    <input type="hidden" name="{$input.name|escape:'htmlall':'UTF-8'}" id="{$input.name|escape:'htmlall':'UTF-8'}">
    <script type="text/javascript">
      (function () {
        function setInput() {
          var config = [];
          var position = 0;
          $('.sortable > li').each(function (index, elem) {
            var $elem = $(elem);
            config.push({
              id: $elem.attr('data-method'),
              position: position++,
              enabled: $elem.find('input[type=checkbox]').is(':checked'),
            });
          });
          $('#{$input.name|escape:'javascript':'UTF-8'}').val(JSON.stringify(config));
        }

        function setPositions() {
          var index = 0;
          $('.sortable > li').each(function (index, elem) {
            var $elem = $(elem);
            $elem.attr('data-pos', index++);
            $elem.find('.positions').text(index);
          });
        }

        function moveUp(event) {
          var $elem = $(event.target).closest('li');
          $elem.prev().insertAfter($elem);
          setPositions();
        }

        function moveDown(event) {

          var $elem = $(event.target).closest('li');
          console.log($elem);
          $elem.next().insertBefore($elem);
          setPositions();
        }

        function init () {
          if (typeof $ === 'undefined') {
            setTimeout(init, 100);
            return;
          }

          $('.sortable').sortable({
            forcePlaceholderSize: true
          }).on('sortupdate', function (event, ui) {
            setPositions();
            setInput();
          });
          $('.sortable > li').each(function (index, elem) {
            var $elem = $(elem);
            $elem.find('a.mollie-up').click(moveUp);
            $elem.find('a.mollie-down').click(moveDown);
            $elem.find('input[type=checkbox]').change(setInput);
          });
          setInput();
        }
        init();
      }());
    </script>
  {elseif $input.type === 'mollie-h1'}
    <br>
    <h1>{$input.title|escape:'html':'UTF-8'}</h1>
  {elseif $input.type === 'mollie-h2'}
    <br>
    <h2>{$input.title|escape:'html':'UTF-8'}</h2>
  {elseif $input.type === 'mollie-h3'}
    <br>
    <h3>{$input.title|escape:'html':'UTF-8'}</h3>
  {else}
    {$smarty.block.parent}
  {/if}
{/block}
