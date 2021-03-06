<?php

/*
 * Copyright (C) 2015 Fabio Colella
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 */

/**
 * Just a footer.
 *
 * @author Fabio Colella
 */
class Footer 
{
    public function __construct() 
    {
        echo <<<HTML
    <div class="clear"></div>
    <footer>
      <p>
        This site is not affiliated nor endorsed by 
         <a href="http://www.fishermansfriend.com/en-gb/pages/contact-us/" target="_blank">
         Lofthouse of Fleetwood Ltd</a>.
         Fisherman's Friend&#174; is a trademark of Lofthouse of Fleetwood Ltd.<br>
         Website created by <a href="https://github.com/fcole90" target="_blank">Fabio Colella</a>.
         Released under <a href="http://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html" target="_blank">
          GPL2</a>. 
          Code available on <a href="https://github.com/fcole90/fisherman-locator" target="_blank">GitHub</a>. Copyright &copy; 2015-2021 Fabio Colella.
      </p>
    </footer>    
HTML;
    }
}
