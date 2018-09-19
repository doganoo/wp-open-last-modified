<?php
/**
 *  Copyright (C) <2016>  <Dogan Ucar>
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  For further questions please visit www.dogan-ucar.de/wp-open-last-modified
 */

/**
 * Class Options
 */
class Options {

	/**
	 * @param $index
	 * @param $value
	 *
	 * @return bool
	 */
	public function update( $index, $value ) {
		if ( $index === "" ) {
			return false;
		}
		if ( $value === "" ) {
			return false;
		}

		return update_option( $index, $value, true );
	}

	/**
	 * @param        $index
	 * @param string $default
	 *
	 * @return mixed|void
	 */
	public function read( $index, $default = "" ) {
		return get_option( $index, $default );
	}

	/**
	 * @param $index
	 *
	 * @return bool
	 */
	public function delete( $index ) {
		return delete_option( $index );
	}
}