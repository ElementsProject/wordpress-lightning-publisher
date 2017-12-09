jQuery(function($) {
  var strike_url = LN_paywall.strike_url
    , ajax_url   = LN_paywall.ajax_url

  function show_pay(post_id, btn) {
    $.post(ajax_url, { action: 'ln_paywall_invoice', post_id: post_id})
      .success(function(invoice_id, state, res) {
        $(btn).closest('.paywall-pay').replaceWith(pay_frame(invoice_id))
      })
      .fail(function(err) { throw err })
  }

  function get_access(invoice_id) {
    $.post(ajax_url, { action: 'ln_paywall_token', invoice_id: invoice_id })
      .success(function(body, state, res) {
        // @TODO get post URL?
        location.href = body.url
      })
      .fail(function(err) { throw err })
  }

  function pay_frame(invoice_id) {
    var frame_url = strike_url + '/checkout/' + invoice_id
    return $('<iframe>').addClass('ln-paywall-frame').attr('src', frame_url)
  }

  $('[data-paywall-postid]').click(function(e) {
    e.preventDefault()
    $(this).attr('disabled', true)
    var post_id = $(this).data('paywall-postid')
    show_pay(post_id, this)
  })

  $(window).on('message', function(ev) {
    var ov = ev.originalEvent
    if (ov.origin !== strike_url.replace(/([^/:])\/.*/, '$1')) return;
    switch (ov.data.type) {
      case 'height':
        $('.ln-paywall-frame').height(ov.data.value)
        break
      case 'completed':
        get_access(ov.data.invoice)
        break
    }
  })

})
