var minify = require("uglify-js").minify;

var input = [];
var output;
process.stdin.on("data", function (d) {
	input.push(d);
});
process.stdin.on("end", function () {
	input = Buffer.concat(input).toString();
	try {
		output = minify(input, {
			fromString: true
		}).code;
	} catch( e ) {
		process.stderr.write( JSON.stringify( e ) );
		process.exit(1);
	}
	process.stdout.write( output );
});
