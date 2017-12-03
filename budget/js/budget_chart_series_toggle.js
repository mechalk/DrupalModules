/**
 * @file
 */

(function ($) {
  'use strict';
  Drupal.behaviors.budget = {
    attach: function () {
      // hard-code color indices to prevent them from shifting as
      // countries are turned on/off.
      var options = drupalSettings.flot.placeholder.options;
      options.legend.backgroundOpacity = 1;
      options.legend.labelFormatter = function(label,series) {
         var dataLength = series.data.length;
         var amount = 0;
         for(var i = 0; i < dataLength; i++)
         {
            amount += series.data[i][1];
         }

         if(amount == 0)
         {
            return null;
         }
         else if(label.includes("Total"))
         {
            return null;
         }
         else
         {
            return label;
         }
      }
      var datasets = drupalSettings.flot.placeholder.data;
      var i = 0;
      $.each(datasets, function (key, val) {
        val.color = i;
        ++i;
      });

      // Insert checkboxes.
      var choiceContainer = $('#choices');
      $.each(datasets, function (key, val) {
         if(val.label.includes("Total"))
         {
            choiceContainer.append("<br/><input type='checkbox' name='" + key +
                                   "' id='id" + key + "'></input>" +
                                    "<label for='id" + key + "'>" + val.label + '</label>');
         }
         else
         {
            choiceContainer.append("<br/><input type='checkbox' name='" + key +
                                   "' checked='checked' id='id" + key + "'></input>" +
                                    "<label for='id" + key + "'>" + val.label + '</label>');
         }
      });

      choiceContainer.find('input').click(plotAccordingToChoices);

      function plotAccordingToChoices() {
        var data = [];
        choiceContainer.find('input:checked').each(function () {
          var key = $(this).attr('name');
          if (key && datasets[key]) {
            data.push(datasets[key]);
          }
        });
        if (data.length > 0) {
          $.plot('#placeholder', data, options);
        }
      }
      plotAccordingToChoices();
    }
  };
}(jQuery));
