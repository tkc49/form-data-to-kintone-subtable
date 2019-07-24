<?php
/**
 * Plugin Name: Form data to kintone Subtable.
 * Plugin URI:
 * Description: This plugin is an addon for "kintone form".
 * Version: 1.0.0
 * Author: Takashi Hosoya
 * Author URI:  http://ht79.info/
 * License: GPLv2
 * Text Domain: kintone-form-attachments
 * Domain Path: /languages
 */
/**
 * Copyright (c) 2017 Takashi Hosoya ( http://ht79.info/ )
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define( 'KINTONE_FORM_SUBTABLE_URL', plugins_url( '', __FILE__ ) );
define( 'KINTONE_FORM_SUBTABLE_PATH', dirname( __FILE__ ) );


$kintoneFormSubtable = new KintoneFormSubtable();
$kintoneFormSubtable->register();


class KintoneFormSubtable {
	private $version = '';
	private $langs   = '';
	private $nonce   = 'kintone_form_subtable_';

	function __construct() {
		$data          = get_file_data(
			__FILE__,
			array( 'ver' => 'Version', 'langs' => 'Domain Path' )
		);
		$this->version = $data['ver'];
		$this->langs   = $data['langs'];

	}

	public function register() {
		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 1 );
	}

	public function plugins_loaded() {
		load_plugin_textdomain(
			'kintone-form-subtable',
			false,
			dirname( plugin_basename( __FILE__ ) ) . $this->langs
		);
		add_filter( 'kintone_form_subtable', array( $this, 'kintone_form_subtable' ), 10, 7 );
		add_filter( 'kintone_fieldcode_supported_list', array( $this, 'kintone_fieldcode_supported_list' ), 10, 1 );

	}

	public function kintone_form_subtable( $subtable_records, $appdata, $kintone_form_properties_data, $kintone_setting_data, $cf7_send_data, $kintone_fields_and_cf7_mailtag_relate_data, $e ) {

		$subtable_values = array();


		foreach ( $kintone_form_properties_data['fields'] as $kintone_form_subtable_properties_data ) {
			// 通常処理データを貰う
			$subtable_values[] = $this->generate_format_kintone_data( $kintone_setting_data, $appdata, $kintone_fields_and_cf7_mailtag_relate_data, $kintone_form_subtable_properties_data, $cf7_send_data, $e );
		}

		foreach ( $subtable_values as $subtable_fieldcode_values ) {

			if ( ! empty( $subtable_fieldcode_values ) ) {
				foreach ( $subtable_fieldcode_values as $fieldcode => $values ) {
					$subtable_record_count = 0;
					foreach ( $values as $value ) {
						$subtable_records['value'][ $subtable_record_count ]['value'][ $fieldcode ] = $value;
						$subtable_record_count ++;
					}
				}
			}
		}

		return $subtable_records;


	}

	public function kintone_fieldcode_supported_list( $kintone_fieldcode_supported_list ) {
		$kintone_fieldcode_supported_list['SUBTABLE'] = '';

		return $kintone_fieldcode_supported_list;
	}

	private function generate_format_kintone_data( $kintone_setting_data, $appdata, $kintone_fields_and_cf7_mailtag_relate_data, $kintone_form_field_properties, $cf7_send_data, $e ) {

		$formated_kintone_value = array();

		// CF7の設定画面で関連付けされたデーターをベースにループ
		foreach ( $kintone_fields_and_cf7_mailtag_relate_data['setting'] as $related_kintone_fieldcode => $related_cf7_mail_tag ) {

			// メールタグが設定されているものだけ kintoneのフィールドにあわせる
			if ( ! empty( $related_cf7_mail_tag ) ) {

				if ( $kintone_form_field_properties['code'] == $related_kintone_fieldcode ) {

					// SUBTABLEの場合は $related_cf7_mail_tag に「__数値」が設定されるので、数値分ループする
					$count = 1;
					// 値のありなし関係なく、セットされていたらOK
					while ( isset( $cf7_send_data[ $related_cf7_mail_tag . '__' . $count ] ) ) {

						// $hoge['fieldcode'][0]['value']['値']
						// $hoge['fieldcode'][1]['value']['値']
						// $hoge['fieldcode'][2]['value']['値']
						$formated_kintone_value[ $kintone_form_field_properties['code'] ][] = KintoneForm::get_formated_data_for_kintone( $kintone_setting_data, $appdata, $kintone_form_field_properties, $cf7_send_data, $related_cf7_mail_tag . '__' . $count, $e );
						$count ++;
					}

					// 一致するのがあり、値の取得ができたのでループを抜ける
					break;
				}
			}
		}

		return $formated_kintone_value;
	}
}
