module.exports = {
  entry: './js/index.js',
  mode: 'production',
  output: {
    path: __dirname + '/public/js'
  },
  module: {
    rules: [
        {test: /\.js$/, use: "babel-loader"}
    ]
  },
  performance: {
    maxAssetSize: 500000,
    maxEntrypointSize: 500000,
  }
};

//workarround of a webpack bug not allowing to exit webpack regularly
process.once('SIGINT', () => {
    console.log("")
    process.exit();
});
