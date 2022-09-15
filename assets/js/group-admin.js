(function( $ ) {
    'use strict';


    $(document).ready(function() {
        var btnPodcasbtnPodcastsGroupDatasData = btnPodcastsGroupData;
        console.log(btnPodcastsGroupData);
        $(document).on('click', '.upload-cover-img', function(e) {
            e.preventDefault();
            var $wrap = $(this).closest('.group-img-wrap'),
                $figure = $('figure', $wrap),
                $input = $('input.cover-art', $wrap),
                $removeBtn = $('.remove-cover-img', $wrap),
                frame;
			if (frame) {
				frame.open();
				return;
			}
			frame = wp.media({
				button: {
					text: btnPodcastsGroupData.buttonText
				},
				states: [
					new wp.media.controller.Library({
						title:     btnPodcastsGroupData.title,
						library:   wp.media.query({ type: 'image' }),
						multiple:  false,
						date:      false
					})
				]
			});

			frame.on( "select", function() {
                // Grab the selected attachment.
                var attachment = frame.state().get("selection").first(),
                    sizes = (  attachment.attributes.sizes ) ?  attachment.attributes.sizes : false,
                    size = ( sizes && sizes.medium ) ? sizes.medium : sizes.full,
                    url = size.url;
				frame.close();
				$figure.html("<img src=\'" + url + "\' />");
                $input.val( attachment.attributes.id );
				$removeBtn.css("display","inline-block");
            });
            frame.open();
        });

        $(".remove-cover-img").click(function(e) {
            e.preventDefault();
            var $wrap = $(this).closest('.group-img-wrap'),
                $figure = $('figure', $wrap),
                $input = $('input.cover-art', $wrap);
            $(this).css("display","none");
            $figure.html("");
            $input.val("");
        });

    });

})( jQuery );
