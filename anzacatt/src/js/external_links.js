/**
 * External Link detector.
 */
(function($, Drupal, window, document, undefined) {

  var current_domain = '';
  var domainRe = /https?:\/\/((?:[\w\d-]+\.)+[\w\d]{2,})/i;

  function domain(url) {
    var arr = domainRe.exec(url);
    return (arr !== null) ? arr[1] : current_domain;
  }

  function isExternalRegexClosure(url) {
    return current_domain !== domain(url);
  }

  Drupal.behaviors.anzacatt_external_links = {
    attach: function(context, settings) {
      // Get current domain.
      current_domain = domain(location.href);

      // Find all links and apply a rel if external.
      $('a', context).each(function() {
        var $this = $(this);
        // No styling on links that directly wrap images.
        if ($this.children('img').length > 0 || $this.children('picture').length > 0) {
          $this.addClass('no-style');
        }
        if (isExternalRegexClosure($this.attr('href'))) {
          $this.attr('rel', 'external');
          $this.attr('target', '_blank');
        }
      });
      // If admin - remove style off admin based link elements.
      if (document.querySelector('body').classList.contains('logged-in')) {
        $('a', '.contextual-links-wrapper').addClass('no-style');
        $('a', '#panels-ipe-control-container').addClass('no-style');
      }
    }
  };

})(jQuery, Drupal, this, this.document);
