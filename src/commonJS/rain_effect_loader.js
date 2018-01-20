var $ = require('jQuery');
var _ = require('_');

import RainRenderer from './rain_effect_loader/rain-renderer';		
import Raindrops from './rain_effect_loader/raindrops';			
import loadImages from './rain_effect_loader/image-loader';		
import createCanvas from './rain_effect_loader/create-canvas';		
import times from './rain_effect_loader/times';
import {random} from './rain_effect_loader/random';

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
		
		var self = this;
		
		// get background img src 
		var srcBg;
		if ( this.el.srcset ){
			
			// get the img srcset and build an array of objects { width: ..., src: ... }
			let srcBgs  =  _.map( this.el.srcset.split(',').map( function( val, index ) {
				return val.trim().split(' ').map( function( val, index ) {
					if ( index === 0 ) {
						return val;
					} else if ( val.indexOf('w') ){
						return val.replace('w','');
					} else {
						return null;
					}
				});
			}), function ( val, index){
					return {
						width: parseInt(val[1]),
						src: val[0]
					};
			});
			
			// filter the array for images smaller than window
			let srcBgsFilterd = _.filter( srcBgs, function( val ){ return $( window ).width() >= val.width; });
			
			// take the largest image from filtered array, or smallest from array
			if ( srcBgsFilterd.length < 1 ) {
				srcBg = _.min( srcBgs, function(val){ return val.width; }).src;
			} else {
				srcBg = _.max( srcBgsFilterd , function(val){ return val.width; }).src;
			}
			
		} else {
			srcBg = this.el.src;
		}
		
		// get foreground img src, load images and start rain
		$.post( rain_localize.ajaxurl, {
			'action': 'rain_thumbnail',
			'srcFull': this.el.src
		} ).done( function( response ){
			response = $.parseJSON( response );
			if ( ! response.hasOwnProperty('srcThumbnail') ) { return; }	
			loadImages([
				{ name:'foreground', src: response.srcThumbnail },
				{ name:'background', src: srcBg },
				{ name:'dropShine', src: rain_localize.images.dropShine },
				{ name:'dropAlpha', src: rain_localize.images.dropAlpha },
				{ name:'dropColor', src: rain_localize.images.dropColor },
			]).then( ( images ) => {
				self.textures = images;
				self.startRain( );
			});
		});

	}
	
	startRain(){
		// console.log( this.textures  );
	
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
		var self = this;
		
		// scroll
		document.addEventListener( 'scroll', ( event ) => {
			this.updateGeometrie();
			this.updateViewport();
			this.renderer.inView = this.inView;
			this.raindrops.inView = this.inView;
		});
		
		// resize
		let resizeDebounce = _.debounce(function() {
			self.updateCss();
		}, 100);
		window.addEventListener('resize', resizeDebounce );
		window.addEventListener('resize', ( event ) => {
			this.updateGeometrie();
			this.updateViewport();
		});
		
		// mousemove
		document.addEventListener('mousemove',(event)=>{
			this.setupParallax(event);
		});
		
		// customizer
		if ( 'undefined' !== typeof wp && wp.customize && wp.customize.selectiveRefresh ) {
			wp.customize.selectiveRefresh.bind( 'partial-content-rendered', function( partial ) {
				if ( ! document.contains( self.el ) && self.enabled ){
					_.each( partial.container, function(element, index, list){
						$( element ).find('img.rain-effect').each( function( index ){
							$( this ).load(function(){
								let rainImage = new RainImage( this, index );
								self.enabled = false;
							});
						});			
					});
				}
			} );
		}
	
	}
	
	setupParallax(event){
		if ( this.inView ){
			
			let scrollPosTop = (window.pageYOffset || document.documentElement.scrollTop)  - (document.documentElement.clientTop || 0);
			
			let x = event.pageX - this.position.left;
			let y = event.pageY - scrollPosTop;

			this.renderer.parallaxX = ((x/this.canvas.width)*2)-1;
			this.renderer.parallaxY = ((y/this.canvas.height)*2)-1;
			
		}
	}

}


$(document).ready(function(){
		
	$( window ).load(function(){

		let rainImages = $('img.rain-effect');
		rainImages.each( function( index ){
			let rainImage = new RainImage( this );
		});

	});
	
});
