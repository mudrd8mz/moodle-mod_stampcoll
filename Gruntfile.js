"use strict";

module.exports = function (grunt) {
    // Load all grunt tasks.
    grunt.loadNpmTasks("grunt-contrib-less");
    grunt.loadNpmTasks("grunt-contrib-watch");
    grunt.loadNpmTasks('grunt-stylelint');

    grunt.initConfig({
        watch: {
            // If any .less file changes in directory "less" then run the tasks.
            files: "less/*.less",
            tasks: ["stylelint", "less"]
        },
        less: {
            // Production config is also available.
            development: {
                options: {
                    // Specifies directories to scan for @import directives when parsing.
                    // Default value is the directory of the source, which is probably what you want.
                    paths: ["less/"],
                    compress: true
                },
                files: {
                    "styles.css": "less/styles.less"
                }
            },
        },
		stylelint: {
			less: {
				options: {
					syntax: 'less',
					configOverrides: {
						rules: {
							// These rules have to be disabled in .stylelintrc for scss compat.
							"at-rule-no-unknown": true,
						}
					}
				},
				src: ['less/*.less']
			}
		}
    });

    // The default task (running "grunt" in console).
    grunt.registerTask("default", ["stylelint", "less"]);
};
