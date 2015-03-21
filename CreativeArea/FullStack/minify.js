var minify = require("uglify-js").minify;

var input = [];
var output;
process.stdin.on("data", function (d) {
	input.push(d);
});
process.stdin.on("end", function () {
	console.log(minify(Buffer.concat(input).toString(), {
		fromString: true
	}).code);
});
