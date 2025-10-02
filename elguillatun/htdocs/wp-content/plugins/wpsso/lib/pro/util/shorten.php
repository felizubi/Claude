<?php
/*
 * IMPORTANT: READ THE LICENSE AGREEMENT CAREFULLY. BY INSTALLING, COPYING, RUNNING, OR OTHERWISE USING THE WPSSO CORE PREMIUM
 * APPLICATION, YOU AGREE  TO BE BOUND BY THE TERMS OF ITS LICENSE AGREEMENT. IF YOU DO NOT AGREE TO THE TERMS OF ITS LICENSE
 * AGREEMENT, DO NOT INSTALL, RUN, COPY, OR OTHERWISE USE THE WPSSO CORE PREMIUM APPLICATION.
 *
 * License URI: https://wpsso.com/wp-content/plugins/wpsso/license/premium.txt
 *
 * Copyright 2012-2024 Jean-Sebastien Morisset (https://wpsso.com/)
 */

if ( ! defined( 'ABSPATH' ) ) {

	die( 'These aren\'t the droids you\'re looking for.' );
}

if ( ! class_exists( 'WpssoProUtilShorten' ) ) {

	class WpssoProUtilShorten {

		private $p;	// Wpsso class object.

		private $svc_instances = array();	// Array of service class objects.

		public function __construct( &$plugin ) {

			$this->p =& $plugin;

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( ! extension_loaded( 'curl' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'curl extension not available - shortening disabled' );
				}

			} elseif ( empty( $this->p->options[ 'plugin_shortener' ] ) || $this->p->options[ 'plugin_shortener' ] === 'none' ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'no shortening service selected - shortening disabled' );
				}

			} else {

				$this->p->util->add_plugin_actions( $this, array(
					'clear_mod_cache' => 1,
				) );

				$this->p->util->add_plugin_filters( $this, array(
					'get_short_url' => 3,
				) );
			}
		}

		public function action_clear_mod_cache( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->clear_short_url( $mod );
		}

		public function filter_get_short_url( $long_url, $svc_id, $mod = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			return $this->get_short_url( $long_url, $svc_id, $mod );
		}

		public function clear_short_url( array $mod ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$clear_count   = 0;
			$canonical_url = $this->p->util->get_canonical_url( $mod );
			$cache_md5_pre = 'wpsso_s_';

			foreach( $this->p->cf[ 'form' ][ 'shorteners' ] as $svc_id => $name ) {

				$cache_salt = __CLASS__ . '::get_short_url(long_url:' . $canonical_url . '_svc_id:' . $svc_id . ')';
				$cache_id   = $cache_md5_pre . md5( $cache_salt );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'canonical url = ' . $canonical_url );
					$this->p->debug->log( 'cache salt = ' . $cache_salt );
					$this->p->debug->log( 'cache id = ' . $cache_id );
				}

				$clear_count += delete_transient( $cache_id );
			}

			return $clear_count;
		}

		public function get_short_url( $long_url, $svc_id, $mod = false ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			$min_shorten = isset( $this->p->options[ 'plugin_min_shorten' ] ) ? $this->p->options[ 'plugin_min_shorten' ] : 23;

			if ( is_array( $mod ) ) {

				if ( $mod[ 'is_404' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: is 404 page' );
					}

					return $long_url;

				} elseif ( $mod[ 'is_search' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: is search page' );
					}

					return $long_url;

				} elseif ( ! $mod[ 'is_public' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: object is not public' );
					}

					return $long_url;

				} elseif ( 'auto-draft' === $mod[ 'post_status' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: post status is auto-draft' );
					}

					return $long_url;

				} elseif ( 'trash' === $mod[ 'post_status' ] ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'exiting early: post status is trash' );
					}

					return $long_url;
				}
			}

			if ( strlen( $long_url ) < $min_shorten ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: url is shorter than ' . $min_shorten . ' chars' );
				}

				return $long_url;

			} elseif ( empty( $svc_id ) || 'none' === $svc_id ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: service name is empty' );
				}

				return $long_url;

			} elseif ( ! extension_loaded( 'curl' ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: curl extension not available' );
				}

				return $long_url;

			} elseif ( apply_filters( 'wpsso_shorten_url_disabled', false, $long_url ) ) {

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: shorten url disabled for ' . $long_url );
				}

				return $long_url;
			}

			$cache_md5_pre  = 'wpsso_s_';
			$cache_exp_secs = $this->p->util->get_cache_exp_secs( $cache_md5_pre );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'service id = ' . $svc_id );
				$this->p->debug->log( 'long url = ' . $long_url );
				$this->p->debug->log( 'cache expire = ' . $cache_exp_secs );
			}

			if ( $cache_exp_secs > 0 ) {

				$cache_salt = __CLASS__ . '::get_short_url(long_url:' . $long_url . '_svc_id:' . $svc_id . ')';
				$cache_id   = $cache_md5_pre . md5( $cache_salt );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'cache salt = ' . $cache_salt );
					$this->p->debug->log( 'cache id = ' . $cache_id );
				}

				$short_url = get_transient( $cache_id );

				if ( ! empty( $short_url ) ) {

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'short url retrieved from transient ' . $cache_id );
					}

					return $short_url;	// Stop here.
				}
			}

			if ( ! isset( $this->svc_instances[ $svc_id ] ) ) {

				$this->get_svc_instance( $svc_id );
			}

			if ( ! is_object( $this->svc_instances[ $svc_id ] ) ) {	// Just in case.

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: instance for service id "' . $svc_id . '" not a valid object' );
				}

				return $long_url;
			}

			switch ( $svc_id ) {

				case 'bitly':
				case 'dlmyapp':
				case 'owly':
				case 'yourls':

					$short_url = $this->svc_instances[ $svc_id ]->shorten( $long_url );

					break;

				case 'tinyurl':

					$short_url = $this->svc_instances[ $svc_id ]->create( $long_url );

					break;

				default:

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'unknown shortening service id: ' . $svc_id );
					}

					break;
			}

			if ( empty( $short_url ) ) {

				if ( $cache_exp_secs > 0 ) {

					set_transient( $cache_id, $long_url, HOUR_IN_SECONDS );

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'long url saved to transient cache for ' . HOUR_IN_SECONDS . ' seconds' );
					}
				}

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'exiting early: service id "' . $svc_id . '" returned an empty short url' );
				}

				return $long_url;	// Stop here.

			}

			if ( $cache_exp_secs > 0 ) {

				set_transient( $cache_id, $short_url, $cache_exp_secs );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( 'saving short url saved to transient cache for ' . $cache_exp_secs . ' seconds' );
				}
			}

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( 'long url shortened to ' . $short_url );
			}

			return $short_url;
		}

		public function get_svc_instance( $svc_id ) {

			if ( $this->p->debug->enabled ) {

				$this->p->debug->mark();
			}

			if ( isset( $this->svc_instances[ $svc_id ] ) ) {	// False or object.

				return $this->svc_instances[ $svc_id ];
			}

			$error_pre = sprintf( __( '%s error:', 'wpsso' ), __METHOD__ );

			$this->svc_instances[ $svc_id ] = false;

			if ( ! $classname = $this->load_svc_lib( $svc_id ) ) {	// False or string.

				return false;
			}

			switch ( $svc_id ) {

				case 'bitly':

					if ( empty( $this->p->options[ 'plugin_bitly_access_token' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'bitly token option is empty' );
						}

						if ( $this->p->notice->is_admin_pre_notices() ) {

							$this->p->notice->err( sprintf( __( 'The "%s" option value is empty and required.', 'wpsso' ),
								_x( 'Bitly Generic Access Token', 'option label', 'wpsso' ) ) );
						}

					} else {

						$this->svc_instances[ $svc_id ] = new $classname(
							$this->p->options[ 'plugin_bitly_access_token' ],
							$this->p->options[ 'plugin_bitly_domain' ],
							$this->p->options[ 'plugin_bitly_group_name' ]
						);
					}

					break;

				case 'dlmyapp':

					if ( empty( $this->p->options[ 'plugin_dlmyapp_api_key' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'dlmyapp api_key option value is empty and required' );
						}

						if ( $this->p->notice->is_admin_pre_notices() ) {

							$this->p->notice->err( sprintf( __( 'The "%s" option value is empty and required.', 'wpsso' ),
								_x( 'DLMY.App API Key', 'option label', 'wpsso' ) ) );
						}

					} else {

						$this->svc_instances[ $svc_id ] = new $classname(
							$this->p->options[ 'plugin_dlmyapp_api_key' ]
						);
					}

					break;

				case 'owly':

					if ( empty( $this->p->options[ 'plugin_owly_api_key' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'owly api_key option value is empty and required' );
						}

						if ( $this->p->notice->is_admin_pre_notices() ) {

							$this->p->notice->err( sprintf( __( 'The "%s" option value is empty and required.', 'wpsso' ),
								_x( 'Ow.ly API Key', 'option label', 'wpsso' ) ) );
						}

					} else {

						$this->svc_instances[ $svc_id ] = new $classname( array(
							'key'      => $this->p->options[ 'plugin_owly_api_key' ],
							'protocol' => 'https:'
						) );
					}

					break;

				case 'tinyurl':

					$this->svc_instances[ $svc_id ] = new SuextTinyUrl();

					$this->svc_instances[ $svc_id ]->setTimeOut( 15 );

					$this->svc_instances[ $svc_id ]->setUserAgent( $this->p->cf[ 'plugin' ][ $this->p->id ][ 'short' ] . '/' .
						$this->p->cf[ 'plugin' ][ $this->p->id ][ 'version' ] );

					break;

				case 'yourls':

					/*
					 * Current url without the query string.
					 */
					$current_url = strtok( SucomUtil::get_prot() . '://' . $_SERVER[ 'SERVER_NAME' ] . $_SERVER[ 'REQUEST_URI' ], '?' );

					if ( empty( $this->p->options[ 'plugin_yourls_api_url' ] ) ) {

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( 'yourls api_url option missing' );
						}

						if ( $this->p->notice->is_admin_pre_notices() ) {

							$this->p->notice->err( sprintf( __( 'The "%s" option value is empty and required.', 'wpsso' ),
								_x( 'YOURLS API URL', 'option label', 'wpsso' ) ) );
						}

					} elseif ( $this->p->options[ 'plugin_yourls_api_url' ] === $current_url ) {

						// translators: %s is the current URL from the $_SERVER variable.
						$error_msg = sprintf( __( 'Loop detected: YOURLS API URL matches current URL (%s)', 'wpsso' ), $current_url );

						if ( $this->p->debug->enabled ) {

							$this->p->debug->log( $error_msg );
						}

						if ( is_admin() ) {

							$this->p->notice->err( $error_msg );
						}

						SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );

					} else {

						$this->svc_instances[ $svc_id ] = new $classname(
							$this->p->options[ 'plugin_yourls_api_url' ],
							$this->p->options[ 'plugin_yourls_username' ],
							$this->p->options[ 'plugin_yourls_password' ],
							$this->p->options[ 'plugin_yourls_token' ]
						);
					}

					break;

				default:

					if ( $this->p->debug->enabled ) {

						$this->p->debug->log( 'unknown shortening service id: ' . $svc_id );
					}

					break;
			}

			if ( ! is_object( $this->svc_instances[ $svc_id ] ) ) {

				$this->svc_instances[ $svc_id ] = false;

				// translators: %s is the shortening service id.
				$error_msg = sprintf( __( 'Unable to instantiate the "%s" shortening service.', 'wpsso' ), $svc_id );

				if ( $this->p->debug->enabled ) {

					$this->p->debug->log( $error_msg );
				}

				if ( is_admin() ) {

					$this->p->notice->err( $error_msg );

					SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
				}
			}

			return $this->svc_instances[ $svc_id ];
		}

		private function load_svc_lib( $svc_id ) {

			foreach ( array( 'com/', 'ext/' ) as $sub_dir ) {

				$lib_file = WPSSO_PLUGINDIR . 'lib/' . $sub_dir . $svc_id . '.php';

				$classname = SucomUtil::sanitize_classname( 'su' . $sub_dir . $svc_id );

				if ( file_exists( $lib_file ) ) {

					require_once $lib_file;

					return $classname;
				}
			}

			$error_pre = sprintf( __( '%s error:', 'wpsso' ), __METHOD__ );

			// translators: %s is the shortening service id.
			$error_msg = sprintf( __( 'URL shortening library file for "%s" is missing and required.', 'wpsso' ), $svc_id );

			if ( $this->p->debug->enabled ) {

				$this->p->debug->log( $error_msg );
			}

			if ( is_admin() ) {

				$this->p->notice->err( $error_msg );

				SucomUtil::safe_error_log( $error_pre . ' ' . $error_msg );
			}

			return false;
		}
	}
}
