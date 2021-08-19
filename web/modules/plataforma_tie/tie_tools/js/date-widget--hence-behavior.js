/**
 * @file
 * Date hence widget library.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Attaches the dateWidgetHenceDisplay behavior.
   */
  Drupal.behaviors.dateWidgetHenceDisplay = {
    attach: function (context) {
      var __self = this;
      // Load available hences.
      __self.displayAllHences();
      // Load specific hence.
      $(document).once().on('change', '.js--date-hence-dispatcher input', function (event) {
        __self.displayHence($(this).closest('.js--date-hence-dispatcher'));
      });
    },
    displayAllHences: function () {
      var __self = this;
      $('.js--date-hence-dispatcher').each(function (index) {
        __self.displayHence($(this));
      });
    },
    displayHence: function (element) {
      var __self = this;
      // Our needed variables.
      var date = element.find('input.form-date').val();
      var time = element.find('input.form-time').val();
      var hence = null;
      if (date && time) {
        hence = __self.calculateHence(date + ' ' + time);
      }
      else if (time && !date) {
        date = __self.getCurrentDate();
        hence = __self.calculateHence(date + ' ' + time);
      }
      else {
        if (date) {
          time = __self.getCurrentTime();
          hence = __self.calculateHence(date + ' ' + time);
        }
      }
      // Let's append date hence.
      if (hence) {
        var output = '<input type="text" disabled="disabled" value="' + hence + '"/>';
        element.find('.js--date-hence-output').html(output);
      }
    },
    calculateHence: function (date) {
      var __self = this;
      // Some useful variables.
      var hence = '';
      var timestampNow = Math.round(new Date() / 1000);
      var timestampFrom = Math.round(new Date(date) / 1000);
      // Depending bigger value, operation is different.
      if (timestampNow > timestampFrom) {
        var interval = __self.formatInterval(timestampNow - timestampFrom);
        hence = Drupal.t('@interval ago', {'@interval': interval});
      }
      else if (timestampNow < timestampFrom) {
        var interval = __self.formatInterval(timestampFrom - timestampNow);
        hence = Drupal.t('@interval left', {'@interval': interval});
      }
      else {
        hence = Drupal.t('@count seconds', {'@count': 0});
      }

      return hence;
    },
    getCurrentDate: function () {
      var date = new Date();
      // Let's get needed date parts.
      var year = date.getFullYear();
      var month = ((date.getMonth().toString().length) == 1) ? '0' + (date.getMonth() + 1) : (date.getMonth() + 1);
      var day = ((date.getDate().toString().length) == 1) ? '0' + (date.getDate()) : (date.getDate());

      return year + '-' + month + '-' + day;
    },
    getCurrentTime: function () {
      var date = new Date();
      var time = date.getHours() + ':' + date.getMinutes() + ':' + date.getSeconds();

      return time;
    },
    formatInterval: function (interval, granularity) {
      // Taken from
      // https://www.it-swarm-es.com/es/javascript/cual-es-el-equivalente-de-php-drupal-format-interval-para-javascript/l958060011/
      granularity = typeof granularity !== 'undefined' ? granularity : 2;
      var output = '';
      while (granularity > 0) {
        var value = 0;
        if (interval >= 31536000) {
          value = 31536000;
          output += (output.length ? ' ' : '') + Drupal.formatPlural(Math.floor(interval / value), '1 year', '@count years');
        }
        else if (interval >= 2592000) {
          value = 2592000;
          output += (output.length ? ' ' : '') + Drupal.formatPlural(Math.floor(interval / value), '1 month', '@count months');
        }
        else if (interval >= 604800) {
          value = 604800;
          output += (output.length ? ' ' : '') + Drupal.formatPlural(Math.floor(interval / value), '1 week', '@count weeks');
        }
        else if (interval >= 86400) {
          value = 86400;
          output += (output.length ? ' ' : '') + Drupal.formatPlural(Math.floor(interval / value), '1 day', '@count days');
        }
        else if (interval >= 3600) {
          value = 3600;
          output += (output.length ? ' ' : '') + Drupal.formatPlural(Math.floor(interval / value), '1 hour', '@count hours');
        }
        else if (interval >= 60) {
          value = 60;
          output += (output.length ? ' ' : '') + Drupal.formatPlural(Math.floor(interval / value), '1 min', '@count min');
        }
        else if (interval >= 1) {
          value = 1;
          output += (output.length ? ' ' : '') + Drupal.formatPlural(Math.floor(interval / value), '1 sec', '@count sec');
        }
        interval %= value;
        granularity--;
      }

      return output.length ? output : Drupal.t('0 sec');
    }
  };

})(jQuery, Drupal);
