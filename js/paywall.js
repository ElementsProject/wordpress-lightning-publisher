jQuery(function($) {
  var ajax_url      = LN_paywall.ajax_url
    , strike_url    = LN_paywall.strike_url
    , strike_origin = $('<a>').attr('href', strike_url)[0].origin

  function show_pay(post_id, target) {
    $.post(ajax_url, { action: 'ln_paywall_invoice', post_id: post_id})
      .success(function(invoice_id) { target.replaceWith(pay_frame(invoice_id)) })
      .fail(function(err) { throw err })
  }

  function get_access(invoice_id) {
    $.post(ajax_url, { action: 'ln_paywall_token', invoice_id: invoice_id })
      .success(function(res) { location.href = res.url })
      .fail(function(err) { throw err })
  }

  function pay_frame(invoice_id) {
    return $('<iframe>').addClass('ln-paywall-frame')
      .attr('src', strike_url + '/checkout/' + invoice_id)
  }

  $('[data-paywall-postid]').click(function(e) {
    e.preventDefault()
    var t = $(this)
    t.attr('disabled', true)
    show_pay(t.data('paywall-postid'), t.closest('.paywall-pay'))
  })

  $(window).on('message', function(ev) {
    var ov = ev.originalEvent
    if (ov.origin !== strike_origin) return;
    switch (ov.data.type) {
      case 'height':    $('.ln-paywall-frame').height(ov.data.value); break
      case 'completed': get_access(ov.data.invoice); break
    }
  })
})
