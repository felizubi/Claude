window.gplvault = window.gplvault || {};

(function($, gplvault, settings, wp){
  gplvault = gplvault || {};
  var $document = $(document),
    __ = wp.i18n.__,
    gv = gplvault,
    selectors = settings.selectors || {},
    notifier = gv.common.notifier,
    $gv_license_status_wrapper = $('#gv_license_status_wrapper'),
    console = window.console;
  gv.admin = {};
  gv.admin.selectors = selectors;
  gv.admin.ajaxLocked = false;
  gv.admin.queue = [];
  gv.admin.tmplApiHeader = gv.template('api-header');
  gv.admin.tmplApiStatus = gv.template('status');

  gv.admin.ajax = function (context, data) {
    var options = {};

    gv.admin.ajaxLocked = true;

    options = _.extend(options, data || {}, {context: context});

    return gv.common.ajax(options).always(gv.admin.ajaxAlways);
  };

  gv.admin.ajaxAlways = function (response) {
    gv.admin.ajaxLocked = false;

    if (response.status ) {
      return;
    }

    if ( 'undefined' !== typeof response.debug && window.console && window.console.log ) {
      _.map( response.debug, function( message ) {
        // Remove all HTML tags and write a message to the console.
        window.console.log( wp.sanitize.stripTagsAndEncodeText( message ) );
      } );
    }
  };

  gv.admin.licenseSettingsSuccess = function (response) {
    response = response || {};
    response.payload = response.payload || {};
    var headerHtml = gv.admin.tmplApiHeader({activated: true}),
      statusHtml = gv.admin.tmplApiStatus(response.payload),
      $headerLabel = $('#api_settings_column').find('.gv-card__header-label');

    $headerLabel.replaceWith(headerHtml);
    $gv_license_status_wrapper.html(statusHtml).fadeIn('slow');
    $('#license_deactivation, #check_license, #cleanup_settings').prop('disabled', false);
    $('#gv_activate_api').prop('disabled', true);
    $('#wp__notice-list').find('.notice').remove();

    notifier.add({
      type: 'success',
      title: response.payload.title || __('Success', 'gplvault'),
      content: response.payload.message,
    });

  };

  gv.admin.licenseSettingsError = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    notifier.add({
      type: 'error',
      title: response.payload.title || 'Error!',
      content: response.message,
    });
  }

  gv.admin.activateLicense = function (context, selector) {
    context = context || 'license_activation';
    var inputKey = $('#' + selectors.license.api.input_key),
      inputProduct = $('#' + selectors.license.api.input_product),
      api_key = inputKey.val(),
      product_id = inputProduct.val(),
      promise,
      data = {};

    if (api_key.length < 40 || product_id.length < 1) {
      throw new ValidationError(__('Both Master Key and Product ID fields are required.', 'gplvault'), __('Required Fields!', 'gplvault'));
    }

    data.api_key = api_key;
    data.product_id = product_id;

    $document.trigger('gv-updating-license', {context: context, selector: selector, payload: data});
    promise = gv.admin.ajax(context, data);
    $document.trigger('gv-updated-license', {context: context, selector: selector, payload: data, promise: promise});
    promise.done(gv.admin.licenseSettingsSuccess).fail(gv.admin.licenseSettingsError);
    return promise;
  };

  gv.admin.resetSettingsForm = function () {
    // $('#api_master_key, #api_product_id').val('');
    $('#gv_activate_api').prop('disabled', false);
  };

  gv.admin.licenseDeactivationSuccess = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    var headerHtml = gv.admin.tmplApiHeader({activated: false}),
      countsElems = $('.gv-count-total, .gv-count-plugins, .gv-count-themes'),
      $headerLabel = $('#api_settings_column').find('.gv-card__header-label');

    countsElems.remove();
    $headerLabel.replaceWith(headerHtml);
    $gv_license_status_wrapper.fadeOut('slow').html('');
    gv.admin.resetSettingsForm();
    $('#license_deactivation, #check_license, #cleanup_settings').prop('disabled', true);

    notifier.add({
      type: 'success',
      title: response.payload.title || 'Success',
      content: response.payload.message
    });
  };

  gv.admin.licenseDeactivationError = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    notifier.add({
      type: 'error',
      title: response.payload.title || 'Error',
      content: response.message
    });
  }

  gv.admin.deactivateLicense = function (context, selector) {
    var promise;
    context = context || 'license_deactivation';

    $document.trigger('gv-deactivating-api', {context: context, selector: selector});
    promise = gv.admin.ajax(context);
    promise.done(gv.admin.licenseDeactivationSuccess).fail(gv.admin.licenseDeactivationError);

    $document.trigger('gv-deactivated-api', {context: context, selector: selector, promise: promise});

    return promise;
  };

  gv.admin.licenseStatusSuccess = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    var headerHtml = gv.admin.tmplApiHeader({activated: response.payload.activated}),
      statusHtml = gv.admin.tmplApiStatus(response.payload),
      $headerLabel = $('#api_settings_column').find('.gv-card__header-label');

    $headerLabel.replaceWith(headerHtml);
    $gv_license_status_wrapper.html(statusHtml);

    notifier.add({
      type: 'success',
      title: response.payload.title || __('Success', 'gplvault'),
      content: response.payload.message,
    });
  };

  gv.admin.licenseStatusError = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    notifier.add({
      type: 'error',
      title: response.payload.title || __('Error', 'gplvault'),
      content: response.message,
    });
  };

  gv.admin.checkLicense = function (context, selector) {
    var promise;
    context = context || 'check_license';

    $document.trigger('gv-checking-api', {context: context, selector: selector});
    promise = gv.admin.ajax(context);
    promise.done(gv.admin.licenseStatusSuccess).fail(gv.admin.licenseStatusError);
    $document.trigger('gv-checked-api', {context: context, selector: selector, promise: promise});

    return promise;
  };

  gv.admin.cleanupSettingsSuccess = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    var headerHtml = gv.admin.tmplApiHeader({activated: false}),
      countsElems = $('.gv-count-total, .gv-count-plugins, .gv-count-themes'),
      $headerLabel = $('#api_settings_column').find('.gv-card__header-label');

    countsElems.remove();
    $headerLabel.replaceWith(headerHtml);
    $gv_license_status_wrapper.fadeOut('slow').html('');
    gv.admin.resetSettingsForm();
    $('#license_deactivation, #check_license, #cleanup_settings').prop('disabled', true);

    notifier.add({
      type: 'success',
      title: response.payload.title || __('Success', 'gplvault'),
      content: response.payload.message,
    });
  };

  gv.admin.cleanupSettingsError = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    notifier.add({
      type: 'error',
      title: __('Error', 'gplvault'),
      content: __('Unknown error occurred.', 'gplvault'),
    });
  }

  gv.admin.cleanupSettings = function (context, selector) {
    var promise;
    context = context || 'cleanup_settings';

    $document.trigger('gv-clearing-settings', {context: context, selector: selector});
    promise = gv.admin.ajax(context);
    promise.done(gv.admin.cleanupSettingsSuccess).fail(gv.admin.cleanupSettingsError);

    $document.trigger('gv-cleared-settings', {context: context, selector: selector, promise: promise});

    return promise;
  };

  gv.admin.itemExclusionSuccess = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    notifier.add({
      type: 'success',
      title: response.payload.title || __('Updated', 'gplvault'),
      content: response.payload.message
    });
  };

  gv.admin.itemExclusionError = function (response) {
    response = response || {};
    response.payload = response.payload || {};

    notifier.add({
      type: 'error',
      title: response.payload.title || __('Not Updated', 'gplvault'),
      content: response.message
    });
  };

  gv.admin.excludePlugins = function (context, selector) {
    context = context || 'plugins_exclusion';

    var inputEl = $('#' + selectors.exclusion.plugins.input_id),
      btnEl = $('#' + selectors.exclusion.plugins.button_id),
      promise,
      plugins = inputEl.val();

    $document.trigger('gv-excluding-plugins', {context: context, plugins: plugins});

    promise = gv.admin.ajax(context, {plugins: plugins});
    promise.done(gv.admin.itemExclusionSuccess).fail(gv.admin.itemExclusionError).always(function() {
      selector.prop('disabled', true);
    });
    return promise;
  };

  gv.admin.excludeThemes = function (context, selector) {
    context = context || 'themes_exclusion';

    var inputEl = $('#' + selectors.exclusion.themes.input_id),
      btnEl = $('#' + selectors.exclusion.themes.button_id),
      promise,
      themes = inputEl.val();

    $document.trigger('gv-excluding-themes', {context: context, themes: themes});
    promise = gv.admin.ajax(context, {themes: themes});
    promise.done(gv.admin.itemExclusionSuccess).fail(gv.admin.itemExclusionError).always(function() {
      selector.prop('disabled', true);
    });
    return promise;
  };

  gv.admin.getResolver = function (context) {
    var resolvers = {
      license_activation: gv.admin.activateLicense,
      license_deactivation: gv.admin.deactivateLicense,
      check_license: gv.admin.checkLicense,
      cleanup_settings: gv.admin.cleanupSettings,
      plugins_exclusion: gv.admin.excludePlugins,
      themes_exclusion: gv.admin.excludeThemes,
    }

    return resolvers[context];
  };

  gv.admin.ajaxResolver = function(selector) {
    var context = selector.data('context'),
      resolver = gv.admin.getResolver(context);

    if (resolver) {
      return resolver.call(null, context, selector);
    }

    return gv.admin.ajax(context);
  };



  $( function () {
    var $globalWrapper = $('#' + settings.selectors.page_wrapper),
      $itemExclusionSection = $('#' + settings.selectors.exclusion.section_id),
      items;

    $document.on('click', 'button.gv-hide-pw', function(e) {
      e.preventDefault();
      gplvault.common.togglePW(e);
    });

    if ($itemExclusionSection.length > 0) {
      $itemExclusionSection.on('change', 'select', function( event ) {
        var $instance = $(this),
          $parentContainer;
        $parentContainer = $instance.closest('.gv-fields__container');
        $parentContainer.find('[data-context]').prop('disabled', false);
      });
    }

    if ($globalWrapper.length > 0) {
      $globalWrapper.on('click', '[data-context]', function(event) {
        event.preventDefault();
        if (gv.admin.ajaxLocked) return false;

        var $_instance = $(this);

        if ($_instance.hasClass('gv-has-confirmation')) {
          var confirmation = confirm($_instance.data('confirmation'));

          if (! confirmation) {
            return false;
          }
        }

        $_instance.addClass('updating-message');
        try {
          var result = gv.admin.ajaxResolver($_instance);
          result && result.always(function () {
            $_instance.removeClass('updating-message');
          });

        } catch (e) {
          notifier.add({
            type: 'error',
            title: e.title || e.name,
            content: e,
          });
          $_instance.removeClass('updating-message');

          return false;
        }
      });
    }
  } );
})(jQuery, window.gplvault, window._gvAdminSettings, window.wp);
