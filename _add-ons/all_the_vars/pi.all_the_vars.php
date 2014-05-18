<?php
class Plugin_all_the_vars extends Plugin
{
	private $page_data;

	/**
	 * The {{ all_the_vars }} tag
	 *
	 * @return string
	 */
	public function index()
	{
		$remove_underscored = $this->fetchParam('remove_underscored', true, null, true);

		// Tidy up the context values
		$context = $this->context;
		foreach ($context as $key => $val) {

			// Remove objects. You can't use them in templates.
			if (is_object($val)) {
				unset($context[$key]);
			}

			// Remove underscored variables.
			if ($remove_underscored && Pattern::startsWith($key, '_')) {
				unset($context[$key]);
			}

		}

		// Get extra page data
		$this->page_data = Content::get(URL::getCurrent());

		// CSS
		$output = $this->css->link('all_the_vars');
		if ( ! $this->fetchParam('websafe_font', false, null, true)) {
			$output .= '<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Ubuntu+Mono" />';
		}

		// Create table
		$output .= $this->createTable($context, false);

		// Display on the screen
		die($output);
	}


	/**
	 * Creates an HTML table of key/value pairs
	 *
	 * @param  array $context Key/values
	 * @param  bool $initial  Whether or not this is the initial table
	 * @return string         HTML table
	 */
	private function createTable($context, $initial)
	{
		$output = '<table>
			<thead>
				<tr>
					<th>Var<span>iable</span></th>
					<th>Value</th></tr>
				</thead>
				<tbody>
		';

		foreach ($context as $key => $val) {

			// Handle empties
			if ($val == '' || empty($val)) {
				$val = '<span class="empty">empty</span>';
			}

			// Table-ception if it's an array
			if (is_array($val)) {
				$val = $this->createTable($val, false);
			}

			// Not an array
			else {

				// Force a string
				$val = (string) $val;

				// Do this on the first
				if ($initial) {
					// Handle 'content' and 'content_raw'. They'd only say 'true' by this point.
					if ($key == 'content') { $val = $this->page_data['content']; }
					if ($key == 'content_raw') { $val = $this->page_data['content_raw']; }
				}

				// If there's html in the value, encode it.
				$val = ($val != strip_tags($val) && $val !== '<span class="empty">empty</span>')
				       ? htmlspecialchars($val)
				       : $val;

				// Detect dates (not perfect)
				if ($this->isValidTimeStamp($val)) {
					$val = $val . ' <span class="time">timestamp</span>';
				}

	    }

			// Create row
			$output .= "
				<tr>
					<th>{$key}</th>
					<td>{$val}</th>
				</tr>
			";

		}

		return $output .= '</tbody></table>';
	}


	/**
	 * Returns whether a string is a valid timestamp
	 *
	 * It's not perfect. Can never be.
	 *
	 * @param  string  $timestamp String
	 * @return boolean
	 */
	private function isValidTimeStamp($timestamp)
	{
		return ((string) (int) $timestamp === $timestamp)
		  && ($timestamp <= PHP_INT_MAX)
		  && ($timestamp >= ~PHP_INT_MAX)
		  && (strlen($timestamp) == 10);
	}

}