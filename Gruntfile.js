module.exports = function(grunt) {
    'use strict';

    // Plugin Config
    var plugin_info = {
        version: '3.3.30',
        branches: {
            base:     'master',
            standard: 'upprev/standard',
            pro:      'upprev/pro'
        },
		dev_plugin_dir: 'upprev/',
    };

    var plugin_patterns = {
        pro: [
            { match: /upPrev Base/g, replace: 'upPrev Pro!' },
            { match: /<%= iworks.plugin.version %>/g, replace: plugin_info.version },
            { match: /\/\/<iworks.plugin.free_only([^<]+)/mg, replace: '' },
            { match: /<\/iworks.plugin.free_only>/g, replace: '' },
            { match: /\/\/<iworks.plugin.pro_only>/g, replace: '' },
            { match: /\/\/<\/iworks.plugin.pro_only>/g, replace: '' }
        ],
        standard: [
            { match: /upPrev Base/g, replace: 'upPrev' },
            { match: /<%= iworks.plugin.version %>/g, replace: plugin_info.version },
            { match: /\/\/<iworks.plugin.pro_only([^<]+)/mg, replace: '' },
            { match: /<\/iworks.plugin.pro_only>/g, replace: '' },
            { match: /\/\/<iworks.plugin.free_only>/g, replace: '' },
            { match: /\/\/<\/iworks.plugin.free_only>/g, replace: '' },
            { match: /<%= iworks.plugin.changelog %>/g, replace: (function() {
                var changelog = grunt.file.read('./changelog.txt');
                changelog = changelog.replace(/^(\S|\s)*==.changelog.==\S*/igm, '' ).trim();
                return changelog;
            })() }
        ],
        files: [ { expand: true, src: [
            '**/*.php',
            '**/*.css',
            '**/*.js',
            '**/*.html',
            '**/*.txt',
            '!node_modules/**',
            '!includes/external/**',
            '!Gruntfile.js',
            '!package.json',
            '!build/**',
            '!grunt_tasks/**',
            '!.git/**'
        ], dest: './' } ],
		// BUILD branches.
		plugin_branches: {
			exclude_pro: [
				'./README.MD',
				'./readme.txt',
				'./screenshot-*',
			],
			exclude_free: [
				'./README.MD',
				'./inc/external/wpmudev-dashboard',
				'./js/cs-cloning.js',
				'./js/cs-cloning.min.js',
				'./js/cs-visibility.js',
				'./js/cs-visibility.min.js',
				'./css/cs-cloning.css',
				'./css/cs-cloning.min.css',
				'./css/cs-visibility.css',
				'./css/cs-visibility.min.css',
				'./views/import.php',
				'./views/quick-edit.php',
				'./views/col-sidebars.php',
				'./inc/class-custom-sidebars-cloning.php',
				'./inc/class-custom-sidebars-export.php',
				'./inc/class-custom-sidebars-visibility.php',
			],
			include_files: [
				'**',
				'!css/src/**',
				'!js/src/**',
				'!js/vendor/**',
				'!img/src/**',
				'!node_modules/**',
				'!Gruntfile.js',
				'!package.json',
				'!build/**',
				'!tests/**',
				'!**/css/src/**',
				'!**/css/sass/**',
				'!**/js/src/**',
				'!**/js/vendor/**',
				'!**/img/src/**',
				'!**/node_modules/**',
				'!**/**.log',
				'!**/tests/**',
				'!**/release/*.zip',
				'!release/*.zip',
				'!**/release/**',
				'!release/**',
				'!**/Gruntfile.js',
				'!**/package.json',
				'!**/build/**',
				'!.sass-cache/**',
				'!.git/**',
				'!.git',
				'!.log',
			],
			base: 'master',
			pro: 'upprev-pro',
			free: 'upprev',
		},
    };

	// Grunt configuration.
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),

        // Plugin config
        build: {
            pro: {
            },
            standard: {
            }
        },

        // Git config
        gitcheckout: {
            pro: {
                options: { branch: plugin_info.branches.pro, overwrite: true }
            },
            standard: {
                options: { branch: plugin_info.branches.standard, overwrite: true }
            },
            base: {
                options: { branch: plugin_info.branches.base }
            }
        },
        gitadd: {
            pro: {
                options: { all: true }
            },
            standard: {
                options: { all: true }
            }
        },
        gitcommit: {
            pro: {
                options: { message: "Built from '" + plugin_info.branches.base + "'", allowEmpty: true },
                files: { src: ['.'] }
            },
            standard: {
                options: { message: "Built from '" + plugin_info.branches.base + "'", allowEmpty: true },
                files: { src: ['.'] }
            }
        },

        // Cleanup config
        clean: {
            pro: [
                "./readme.txt",
            ],
            standard: [
                "./includes/classes/class.basic.certificate.php",
                "./includes/external/dashboard/",
                './includes/plugins/*marketpress*.zip',
                './readme.md',
                './changelog.txt'
            ]
        },

        // Replace config
        replace: {
            pro: {
                options: {
                    patterns: plugin_patterns.pro
                },
                files: plugin_patterns.files
            },
            standard: {
                options: {
                    patterns: plugin_patterns.standard
                },
                files: plugin_patterns.files
            }
        },

        // i18n config
		makepot: {
		    target: {
		        options: {
					domainPath: '/languages',
					mainFile: 'upprev.php',
					potFilename: 'cp-default.pot',
					potHeaders: {
						'poedit': true,
						'language-team': 'WPMU Dev <support@iworks.org>',
						'report-msgid-bugs-to': 'http://wordpress.org/support/plugin/upprev',
						'last-translator': 'WPMU Dev <support@iworks.org>',
						'x-generator': 'grunt-wp-i18n'
					},
		            type: 'wp-plugin'
		        }
		    },
            dev: {
                options: {
                    domainPath: '/languages',
                    mainFile: 'upprev.php',
                    potFilename: 'cp-default.pot',
                    potHeaders: {
                        'poedit': true,
                        'language-team': 'WPMU Dev <support@iworks.org>',
                        'report-msgid-bugs-to': 'http://wordpress.org/support/plugin/upprev',
                        'last-translator': 'WPMU Dev <support@iworks.org>',
                        'x-generator': 'grunt-wp-i18n'
                    },
                    type: 'wp-plugin'
                }
            },
            free: {
                options: {
                    domainPath: '/languages',
                    mainFile: 'upprev.php',
                    potFilename: 'upprev-default.pot',
                    potHeaders: {
                        'poedit': true,
                        'language-team': 'WPMU Dev <support@iworks.org>',
                        'report-msgid-bugs-to': 'http://wordpress.org/support/plugin/upprev',
                        'last-translator': 'WPMU Dev <support@iworks.org>',
                        'x-generator': 'grunt-wp-i18n'
                    },
                    type: 'wp-plugin'
                }
            }
		},

		// BUILD - Copy all plugin files to the release subdirectory.
		copy: {
			pro: {
				src: plugin_patterns.plugin_branches.include_files,
				dest: 'release/<%= pkg.version %>-pro/'
			},
			free: {
				src: plugin_patterns.plugin_branches.include_files,
				dest: 'release/<%= pkg.version %>-free/'
			},
		},


		// BUILD - Create a zip-version of the plugin.
		compress: {
			pro: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-pro-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>-pro/',
				src: [ '**/*' ],
				dest: plugin_info.dev_plugin_dir
			},
			free: {
				options: {
					mode: 'zip',
					archive: './release/<%= pkg.name %>-free-<%= pkg.version %>.zip'
				},
				expand: true,
				cwd: 'release/<%= pkg.version %>-free/',
				src: [ '**/*' ],
				dest: plugin_info.dev_plugin_dir
			},
		},

		wpmu_pot2mo: {
		    files: {
		        src: 'languages/*.pot',
		        expand: true
		    }
		}
	});

	// Load grunt modules
	grunt.loadNpmTasks( 'grunt-wp-i18n' );
    grunt.loadNpmTasks('grunt-contrib-clean');
    grunt.loadNpmTasks('grunt-git');
    grunt.loadNpmTasks('grunt-replace');

	// Adapted from https://github.com/MicheleBertoli/grunt-po2mo
	grunt.registerMultiTask('wpmu_pot2mo', 'Compile .pot files into binary .mo files with msgfmt.', function() {
		this.files.forEach(function(file) {

		  var dest = file.dest;
		  if (dest.indexOf('.pot') > -1) {
		      dest = dest.replace('.pot', '.mo');
		  }
		  grunt.file.write(dest);

		  var exec = require('child_process').exec;
		  var command = 'msgfmt -o ' + dest + ' ' + file.src[0];

		  grunt.verbose.writeln('Executing: ' + command);
		  exec(command);

		});
	});

	// Default task(s).
	grunt.registerTask( 'default', ['makepot', 'wpmu_pot2mo'] );

    // Plugin build tasks
    grunt.registerTask('build', 'Run all tasks.', function(target) {

    grunt.option('verbose', true );

    if (target == null) {
    grunt.warn('Target must be specified - build:pro or build:free');
    }

    grunt.log.subhead( 'Update product branch [' + target + ']...' );

                // Checkout the destination branch.
                grunt.task.run('gitcheckout:' + target );

                // Remove code and files that does not belong to this version.
                grunt.task.run('replace:' + target );
                grunt.task.run('clean:' + target );


                //grunt.task.run('makepot:' + target );
                //grunt.task.run('wpmu_pot2mo:' + target );

                // Add the processes/cleaned files to the target branch.
                grunt.task.run('gitadd:' + target );
                grunt.task.run('gitcommit:' + target );



                // Create a distributable zip-file of the plugin branch.
                grunt.task.run( 'clean:release_' + target );
                grunt.task.run( 'copy:' + target );
                grunt.task.run('compress:' + target );



                grunt.task.run('gitcheckout:base');

            });

    // Build pro and standard repo
    grunt.registerTask( 'buildAll', function() {
        grunt.task.run('build:standard');
        grunt.task.run('build:pro');
    } );


};
