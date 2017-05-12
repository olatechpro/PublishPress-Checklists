<?php
/**
 * @package WordPress-EDD-License-Integration
 * @author PressShack
 *
 * Copyright (c) 2017 PressShack
 *
 * This file is part of WordPress-EDD-License-Integration
 *
 * WordPress-EDD-License-Integration is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * WordPress-EDD-License-Integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WordPress-EDD-License-Integration.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PressShack\EDD_License\Setting\Field;

class Text extends Base {
	/**
	 * Returns the field HTML template.
	 *
	 * @return string
	 */
	protected function get_template()
	{
		return '<input type="text" name="%s" id="%s" value="%s" placeholder="%s" />';
	}

	/**
	 * Returns a string version of this class, with the HTML code for the
	 * field.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return vsprintf(
			$this->get_template(),
			array(
				sprintf('[%s][%s]', $this->options_group_name, $this->name ),
				sprintf('%s-%s', $this->options_group_name, $this->id ),
				$this->value,
				$this->placeholder,
			)
		);
	}
}