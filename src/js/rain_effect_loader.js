/**
 * External dependencies
 */
import $ from 'jquery';

/**
 * Internal dependencies
 */
import RainRenderer from './rain_effect_loader/rain-renderer';
import Raindrops from './rain_effect_loader/raindrops';
import loadImages from './rain_effect_loader/image-loader';
import createCanvas from './rain_effect_loader/create-canvas';
import times from './rain_effect_loader/times';
import {random} from './rain_effect_loader/random';

const getWindowWidth = () => window.innerWidth
	|| document.documentElement.clientWidth
	|| document.body.clientWidth;

class RainImage {

	constructor( el ) {
		this.enabled = true;
		this.el = el;
		this.el = el;
		this.$el = $( el );

		this.updateGeometrie();
		this.updateViewport();

		this.setCanvas();
		this.updateCss();

		// load textures and start rain
		this.loadTextures();
	}

	updateGeometrie(){
		this.dimensions = {
			height: this.$el.outerHeight(),
			width: this.$el.outerWidth(),
		};
		this.dimensions.ratio = this.dimensions.height / this.dimensions.width;
		this.position = this.$el.offset();
		this.position.bottom = this.position.top + this.dimensions.height;
		this.position.right = this.position.left + this.dimensions.width;

	}
	updateViewport(){
		this.viewport = {};
		this.viewport.top = $(window).scrollTop();
		this.viewport.bottom = this.viewport.top + $( window ).height();
	}
	inView( tolerance ) {
		tolerance = tolerance || 50;
		return this.position.bottom > this.viewport.top - tolerance && this.position.top < this.viewport.bottom + tolerance;
	}

	setCanvas(){
		this.canvas = createCanvas(this.dimensions.width, this.dimensions.height);
		this.$canvas = $(this.canvas);
		this.$el.after(this.canvas);
		this.fallback = this.el;
	}

	updateCss(){
		let style = document.defaultView.getComputedStyle(this.el, null);
		this.$canvas.css({
			float: style.float,
			margin: style.margin,
			maxWidth: style.maxWidth,
			maxHeight: style.maxHeight,
			height: style.height,
			width: style.width,
			border: style.border,
			verticalAlign: style.verticalAlign,
		});

		if ( style.display !== 'none' ){
			this.$canvas.css({
				display: style.display,
			});
		}

		this.$el.css('display','none');
	}

	loadTextures(){
		const self = this;

		const {
			srcset,
			src,
		} = this.el;

		const {
			ajaxurl,
			images: {
				dropShine,
				dropAlpha,
				dropColor,
			},
		} = rain_effect_loader_data;

		// get background img src
		let srcBg = false;
		if ( srcset ) {
			// get the img srcset and build an array of objects { width: ..., src: ... }
			let srcBgs  = srcset.split(',')
				.map( ( val, index ) => val.trim().split(' ').map( ( val, index ) => {
					if ( index === 0 ) {
						return val;
					} else if ( val.indexOf('w') ){
						return val.replace('w','');
					} else {
						return null;
					}
				} ) )
				.map( ( val, index ) => { return {
					width: parseInt(val[1]),
					src: val[0]
				} } );

			// filter the array for images smaller than window
			const srcBgsFilterd = [...srcBgs].filter( val => getWindowWidth() >= val.width );

			// take the largest image from filtered array, or smallest from array
			let srcBgObj;
			if ( srcBgsFilterd.length < 1 ) {
				srcBgObj = [...srcBgs].reduce( ( p, v ) => ( p.width < v.width ? p : v ) );
			} else {
				srcBgObj = [...srcBgsFilterd].reduce( ( p, v ) => ( p.width > v.width ? p : v ) );
			}
			srcBg = srcBgObj && srcBgObj.src ? srcBgObj.src : false;
		}

		if ( ! srcset || ! srcBg ) {
			srcBg = src;
		}

		// get foreground img src, load images and start rain
		$.post( ajaxurl, {
			'action': 'rain_thumbnail',
			'srcFull': src
		} ).done( function( response ){

			response = $.parseJSON( response );

			if ( ! response.hasOwnProperty('srcThumbnail') ) { return; }
			loadImages([
				{ name:'foreground', src: response.srcThumbnail },
				{ name:'background', src: srcBg },
				{ name:'dropShine', src: dropShine },
				{ name:'dropAlpha', src: dropAlpha },
				{ name:'dropColor', src: dropColor },
			]).then( ( images ) => {
				self.textures = images;
				self.startRain( );
			});
		});

	}

	startRain(){
		this.raindrops = new Raindrops(
			this.inView,
			this.canvas.width,
			this.canvas.height,
			window.devicePixelRatio,
			this.textures.dropAlpha.img,
			this.textures.dropColor.img,
			{
				minR:20,
				maxR:60,
				dropletsRate:25,
				globalTimeScale:0.5,
				trailRate:1.1,
				spawnArea:[-0.3,0.3],
				trailScaleRange:[0.2,0.35],
				dropFallMultiplier:0.2,
				collisionBoost:0.35,
				collisionBoostMultiplier:0.025,
			}
		);

		times(80,(i)=>{
			this.raindrops.addDrop(
			this.raindrops.createDrop({
				x:random(this.canvas.width),
				y:random(this.canvas.height),
				r:random(10,20)
				})
			);
		});

		this.renderer = new RainRenderer(
			this.canvas,
			this.inView,
			this.raindrops.canvas,
			this.textures.foreground.img,
			this.textures.background.img,
			this.textures.dropShine.img,
			{
				brightness:1,
				renderShadow:true,
				minRefraction:150,
				maxRefraction:512,
				alphaMultiply:7,
				alphaSubtract:3
			}
		);

		// on failure ???
		// this.fallback.style.display = 'none';

		this.setupEvents();
	}

	setupEvents(){

		// debounced window scroll event
		let timeoutScroll = false;
		document.addEventListener( 'scroll', () => {
			clearTimeout( timeoutScroll );
			timeoutScroll = setTimeout( () => {
				this.updateGeometrie();
				this.updateViewport();
				this.renderer.inView = this.inView;
				this.raindrops.inView = this.inView;
			}, 100 );
		} );

		// debounced window resize event
		let timeoutResize = false;
		window.addEventListener( 'resize', () => {
			clearTimeout( timeoutResize );
			timeoutResize = setTimeout( () => {
				this.updateCss();
				this.updateGeometrie();
				this.updateViewport();
			}, 100 );
		} );

		// mousemove
		document.addEventListener('mousemove',(event)=>{
			this.setupParallax(event);
		});

		// // ??? TODO FIX THIS
		// // customizer
		// var self = this;
		// if ( 'undefined' !== typeof wp && wp.customize && wp.customize.selectiveRefresh ) {
		// 	wp.customize.selectiveRefresh.bind( 'partial-content-rendered', function( partial ) {
		// 		if ( ! document.contains( self.el ) && self.enabled ){
		// 			each( partial.container, function(element, index, list){
		// 				$( element ).find('img.rain-effect:not(.no-rain)').each( function( index ){
		// 					$( this ).load(function(){
		// 						let rainImage = new RainImage( this, index );
		// 						self.enabled = false;
		// 					});
		// 				});
		// 			});
		// 		}
		// 	} );
		// }
	}

	setupParallax(event){
		if ( this.inView ){

			let scrollPosTop = (window.pageYOffset || document.documentElement.scrollTop)  - (document.documentElement.clientTop || 0);

			let x = event.pageX - this.position.left;
			let y = event.pageY - scrollPosTop;

			this.renderer.parallaxX = ((x/this.canvas.width)*2)-1;
			this.renderer.parallaxY = ((y/this.canvas.height)*2)-2;

		}
	}

}


$(document).ready(function(){
	$( window ).load(function(){
		let rainImages = $('img.rain-effect:not(.no-rain), .wp-block-image.rain-effect:not(.no-rain) img');
		rainImages.each( function( index ){
			let rainImage = new RainImage( this );
		});
	});
});
