
const path = require( 'path' )
const {
	get,
	set,
} = require( 'lodash' );

const webpackGlslify = grunt => {

	grunt.hooks.addFilter( 'config.webpack', 'config.webpack.glslify', config => {

		const rules = get( config, ['all','module','rules'], [] );

		let newRules = [
			...rules,
            {
                test: /\.(glsl|vs|fs|vert|frag)$/,
                exclude: /node_modules/,
                use: [
                    'raw-loader',
                    'glslify-loader',
                    // path.resolve( 'node_modules/raw-loader' ),
                    // path.resolve( 'node_modules/glslify-loader' ),
                ]
            },
		];

		const newConfig = { ...config, };
		set( newConfig, ['all','module','rules'], newRules );

		return newConfig;
	}, 10 );

};

module.exports = webpackGlslify;
