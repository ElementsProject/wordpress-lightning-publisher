jQuery(function($) {
  var ajax_url      = LN_publisher.ajax_url
    , charge_url    = LN_publisher.charge_url
    , charge_origin = $('<a>').attr('href', charge_url)[0].origin

  function show_pay(post_id, target) {
    $.post(ajax_url, { action: 'ln_publisher_invoice', post_id: post_id})
      .success(function(invoice_id) { target.replaceWith(pay_frame(invoice_id)) })
      .fail(function(err) { throw err })
  }

  function get_access(invoice_id) {
    $.post(ajax_url, { action: 'ln_publisher_token', invoice_id: invoice_id })
      .success(function(res) { location.href = res.url })
      .fail(function(err) { throw err })
  }

  function pay_frame(invoice_id) {
    return $('<iframe>').addClass('ln-publisher-frame')
      .attr('src', charge_url + '/checkout/' + invoice_id)
  }

  $('[data-publisher-postid]').click(function(e) {
    e.preventDefault()
    var t = $(this)
    t.attr('disabled', true)
    show_pay(t.data('publisher-postid'), t.closest('.ln-publisher-pay'))
  })

  $(window).on('message', function(ev) {
    var ov = ev.originalEvent
    if (ov.origin !== charge_origin) return;
    switch (ov.data.type) {
      case 'height':    $('.ln-publisher-frame').height(ov.data.value); break
      case 'completed': get_access(ov.data.invoice); break
    }
  })
})
