/*global require*/

/**
 * When grunt command does not execute try these steps:
 *
 * - delete folder 'node_modules' and run command in console:
 *   $ npm install
 *
 * - Run test-command in console, to find syntax errors in script:
 *   $ grunt hello
 */

module.exports = function(grunt) {
    // Show elapsed time at the end.
    require('time-grunt')(grunt);

    // Load all grunt tasks.
    require('load-grunt-tasks')(grunt);

    var buildtime = new Date().toISOString();
    var buildyear = 1900 + new Date().getYear();

    var conf = {
        js_files_concat: {
            'assets/scripts/admin/kpir.js': [
                'assets/scripts/admin/src/datepicker.js',
                'assets/scripts/admin/src/invoice.js',
                'assets/scripts/admin/src/jpk.js',
                'assets/scripts/admin/src/select2.js'
            ]
        },

        css_files_compile: {
            'assets/styles/admin/post-type-invoice.css': 'assets/styles/src/admin/post-type-invoice.scss',
            'assets/styles/admin/wordpress-admin-dashboard.css': 'assets/styles/src/admin/wordpress-admin-dashboard.scss',
            'assets/styles/admin/reports.css': 'assets/styles/src/admin/reports.scss'
        },

        css_files_concat: {
            'assets/styles/kpir-admin.css': [ 'assets/styles/admin/*.css' ]
        },
        // BUILD patterns to exclude code for specific builds.
        replaces: {
            patterns: [
                { match: /AUTHOR_NAME/g, replace: '<%= pkg.author[0].name %>' },
                { match: /AUTHOR_URI/g, replace: '<%= pkg.author[0].uri %>' },
                { match: /BUILDTIME/g, replace: buildtime },
                { match: /IWORKS_RATE_TEXTDOMAIN/g, replace: '<%= pkg.name %>' },
                { match: /IWORKS_OPTIONS_TEXTDOMAIN/g, replace: '<%= pkg.name %>' },
                { match: /PLUGIN_DESCRIPTION/g, replace: '<%= pkg.description %>' },
                { match: /PLUGIN_NAME/g, replace: '<%= pkg.name %>' },
                { match: /PLUGIN_REQUIRES_PHP/g, replace: '<%= pkg.requires.PHP %>' },
                { match: /PLUGIN_REQUIRES_WORDPRESS/g, replace: '<%= pkg.requires.WordPress %>' },
                { match: /PLUGIN_TESTED_WORDPRESS/g, replace: '<%= pkg.tested.WordPress %>' },
                { match: /PLUGIN_TAGLINE/g, replace: '<%= pkg.tagline %>' },
                { match: /PLUGIN_TITLE/g, replace: '<%= pkg.title %>' },
                { match: /PLUGIN_TILL_YEAR/g, replace: buildyear },
                { match: /PLUGIN_URI/g, replace: '<%= pkg.homepage %>' },
                { match: /PLUGIN_VERSION/g, replace: '<%= pkg.version %>' },
                { match: /^Version: .+$/g, replace: 'Version: <%= pkg.version %>' },
            ],
            // Files to apply above patterns to (not only php files).
            files: {
                expand: true,
                src: [
                    '**/*.php',
                    '**/*.css',
                    '**/*.js',
                    '**/*.html',
                    '**/*.txt',
                    '!node_modules/**',
                    '!lib/**',
                    '!docs/**',
                    '!release/**',
                    '!Gruntfile.js',
                    '!package-lock.json',
                    '!build/**',
                    '!tests/**',
                    '!.git/**',
                    '!stylelint.config.js',
                ],
                dest: './release/<%= pkg.version %>/'
            }
        },

        // Regex patterns to exclude from transation.
        translation: {
            ignore_files: [
                'README.md',
                '.git*',
                'node_modules/.*',
                '(^.php)', // Ignore non-php files.
                'inc/external/.*', // External libraries.
                'release/.*', // Temp release files.
                '.sass-cache/.*',
                'tests/.*', // Unit testing.
            ],
            pot_dir: 'languages/', // With trailing slash.
            textdomain: "<%= pkg.name %>",
        },
        dir: "<%= pkg.name %>/",
        plugin_file: 'kpir.php'
    };

    // Project configuration
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // JS - Concat .js source files into a single .js file.
        concat: {
            options: {
                stripBanners: true,
                banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
                    ' * <%= pkg.homepage %>\n' +
                    ' * Copyright (c) <%= grunt.template.today("yyyy") %>\n' +
                    ' * Licensed <%= pkg.license %>\n' +
                    ' */\n'
            },
            scripts: {
                files: conf.js_files_concat
            }
        },

        // JS - Validate .js source code.
        jshint: {
            all: [
                'Gruntfile.js',
                'assets/scripts/src/**/*.js',
                'assets/scripts/test/**/*.js'
            ],
            options: {
                curly: true,
                eqeqeq: true,
                immed: true,
                latedef: true,
                newcap: true,
                noarg: true,
                sub: true,
                undef: true,
                boss: true,
                eqnull: true,
                globals: {
                    exports: true,
                    module: false
                }
            }
        },

        // JS - Uglyfies the source code of .js files (to make files smaller).
        uglify: {
            all: {
                files: [{
                    expand: true,
                    src: ['admin/*.js', '!**/*.min.js', '!shared*'],
                    cwd: 'assets/scripts/',
                    dest: 'assets/scripts/',
                    ext: '.min.js',
                    extDot: 'last'
                }],
                options: {
                    banner: '/*! <%= pkg.title %> - v<%= pkg.version %>\n' +
                        ' * <%= pkg.homepage %>\n' +
                        ' * Copyright (c) <%= grunt.template.today("yyyy") %>;' +
                        ' * Licensed <%= pkg.license %>' +
                        ' */\n',
                    mangle: {
                        reserved: ['jQuery']
                    }
                }
            }
        },

        // WATCH - Watch filesystem for changes during development.
        watch: {
            sass: {
                files: [
                    'assets/sass/**/*.scss',
                    'inc/modules/**/*.scss'
                ],
                tasks: ['sass', 'cssmin'],
                options: {
                    debounceDelay: 500
                }
            },
            scripts: {
                files: ['assets/scripts/src//**/*.js'],
                tasks: ['jshint', 'concat', 'uglify'],
                options: {
                    debounceDelay: 500
                }
            }
        },

        // BUILD - Create a zip-version of the plugin.
        compress: {
            target: {
                options: {
                    mode: 'zip',
                    archive: './release/<%= pkg.name %>.zip'
                },
                expand: true,
                cwd: './release/<%= pkg.version %>/',
                src: ['**/*']
            }
        },

        // BUILD - update the translation index .po file.
        makepot: {
            target: {
                options: {
                    cwd: '',
                    domainPath: conf.translation.pot_dir,
                    exclude: conf.translation.ignore_files,
                    mainFile: conf.plugin_file,
                    potFilename: conf.translation.textdomain + '.pot',
                    potHeaders: {
                        poedit: true, // Includes common Poedit headers.
                        'x-poedit-keywordslist': true // Include a list of all possible gettext functions.
                    },
                    processPot: null, // A callback function for manipulating the POT file.
                    type: 'wp-plugin', // wp-plugin or wp-theme
                    updateTimestamp: true, // Whether the POT-Creation-Date should be updated without other changes.
                    updatePoFiles: true // Whether to update PO files in the same directory as the POT file.
                }
            }
        },

        // BUILD: Replace conditional tags in code.
        replace: {
            target: {
                options: {
                    patterns: conf.replaces.patterns
                },
                files: [conf.replaces.files]
            }
        },

        clean: {
            options: {
                force: true
            },
            release: {
                options: {
                    force: true
                },
                src: ['./release', './release/*', './release/**']
            }
        },

        copy: {
            release: {
                expand: true,
                src: [
                    '*',
                    '**',
                    '!assets/js/src',
                    '!assets/js/src/*',
                    '!assets/js/src/**',
                    '!assets/sass',
                    '!assets/sass/*',
                    '!assets/sass/**',
                    '!bitbucket-pipelines.yml',
                    '!composer.json',
                    '!composer.lock',
                    '!.git',
                    '!.github',
                    '!.github/*',
                    '!.github/**',
                    '!Gruntfile.js',
                    '!.idea', // PHPStorm settings
                    '!languages/*~',
                    '!**/LICENSE',
                    '!LICENSE',
                    '!node_modules',
                    '!node_modules/*',
                    '!node_modules/**',
                    '!package.json',
                    '!package-lock.json',
                    '!phpcs.xml.dist',
                    '!**/README.md',
                    '!README.md',
                    '!stylelint.config.js',
                    '!tests/*',
                    '!tests/**',
                ],
                dest: './release/<%= pkg.version %>/',
                noEmpty: true
            },
        }

    });

    // Test task.
    grunt.registerTask('hello', 'Test if grunt is working', function() {
        grunt.log.subhead('Hi there :)');
        grunt.log.writeln('Looks like grunt is installed!');
    });

    grunt.registerTask('release', 'Generating release copy', function() {
        grunt.task.run('clean');
        grunt.task.run('js');
        grunt.task.run('makepot');
        grunt.task.run('copy');
        grunt.task.run('replace');
        grunt.task.run('compress');
    });

    // Default task.

    grunt.registerTask('build', ['release']);
    grunt.registerTask('default', ['clean', 'jshint', 'concat', 'uglify', 'makepot']);
    grunt.registerTask('js', ['concat', 'uglify']);
    //grunt.registerTask( 'test', ['phpunit', 'jshint'] );

    grunt.task.run('clear');
    grunt.util.linefeed = '\n';

};
