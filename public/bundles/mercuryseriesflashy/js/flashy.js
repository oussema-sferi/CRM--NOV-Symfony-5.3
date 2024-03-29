function flashy(message, link) {
    var template = $($('#flashy-template').html())
    $('.flashy').remove()
    template
        .find('.flashy__body')
        .html(message)
        .attr('href', link || '#')
        .end()
        .appendTo('body')
        .hide()
        .fadeIn(700)
        .delay(6000)
        .animate(
            {
                marginRight: '-100%'
            },
            300,
            'swing',
            function() {
                $(this).remove()
            }
        )
}