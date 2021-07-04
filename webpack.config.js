const path = require('path');
const webpack = require('webpack');

module.exports = {
    plugins: [new webpack.DefinePlugin({
        // Definitions...
      })
    ],
    resolve: {
        alias: {
            '@': path.resolve('resources/js'),
        },
    },
};
