Web({

	load:function( web ) {
		$ = $ || function(){};
		this.fixNav();

		// 所有页面都运行的
		$(() => {
			$("[data-toggle='popover']").popover({trigger:'hover'});
			this.fixTooltip();
		});
	},

	fixNav: function() {
		let offsetTop = 80;
		$(window).scroll(function(event) {
			let top = $(window).scrollTop();
			if ( top >= offsetTop ) {
				$('.nav').css('top', '0px');
				$('.nav').addClass('fixed');

			} else if ( top < offsetTop ) {
				$('.nav').removeClass('fixed');
			}
		});
	},

	/**
	 * 优化 float-tooltip 呈现
	 * @return {[type]} [description]
	 */
	fixTooltip: function(){
		let margin = 32;
		let left =  $('.container').offset().left +  $('.container').width()  +  margin;
		$('.float-tooltip').css('right', '0px');
		$('.float-tooltip').css('left', left + 'px');
		$('.float-tooltip').hide();
		$('.float-tooltip').removeClass('hidden');
		$('.float-tooltip').fadeIn();
	},

	onError:function( error ) {
		console.log( 'Error=', error, SERVICE_URL );
	}

});