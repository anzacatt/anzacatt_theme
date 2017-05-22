/**
 * Global variables.
 */
var desktop_breakpoint = 1200;
var large_tablet_breakpoint = 1024;
var tablet_breakpoint = 768;
var mobile_breakpoint = 420;
var desktop_column = 1170;

/**
 * govCMS general bootstrapping.
 */
(function($, Drupal, window, document, undefined) {

  /**
   * Picks a random element of an array of anything.
   *
   * @param arr
   *   Array for processing.
   * @returns mixed
   *   A random array element.
   */
  function randomFrom(arr) {
    var randomIndex = Math.floor(Math.random() * arr.length);
    return arr[randomIndex];
  }

  Drupal.behaviors.anzacatt = {
    attach: function(context, settings) {
      // Object Fit Polyfill for IE. Used on News Teaser Images.
      objectFitImages();
    }
  };

  Drupal.behaviors.anzacatt_parliament_images = {
    attach: function(context, settings) {
      // Add random parliament images as header background.
      var parliamentaryImages = Drupal.settings.anzacatt.parliament_images;
      // Get parliaments from object keys.
      var parliaments = Object.keys(parliamentaryImages);
      // Get random parliament.
      var randomParliament = randomFrom(parliaments);
      // Get random image for the selected parliament.
      var selectedImage = randomFrom(parliamentaryImages[randomParliament]);
      var element = $('#page .content-header, .front .gov-front-layout .pane-bean-panels .pane-content');
      element.attr('style', 'background-image: url(' + selectedImage + ')');
    }
  }

})(jQuery, Drupal, this, this.document);
