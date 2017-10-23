<?php
/**
 * @version		0.1
 * @package		ExtLinks K2 Plugin (K2 plugin)
 * @author		Pavel Pronskiy
 * @copyright	Copyright (c) 2017 Pavel Pronskiy.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 */

// no direct access
defined('_JEXEC') or die ;


// Load the K2 Plugin API
JLoader::register('K2Plugin', JPATH_ADMINISTRATOR.'/components/com_k2/lib/k2plugin.php');

// Initiate class to hold plugin events
class plgK2ExtLinks extends K2Plugin
{

	function __construct(&$subject, $params)
	{
		parent::__construct($subject, $params);
		$this->options = new stdClass;
		$this->options->jsclick = ($this->params->get('jsclick') != NULL && $this->params->get('jsclick') != '0') ? true : false;
		$this->options->nofollow = ($this->params->get('nofollow') != NULL && $this->params->get('nofollow') != '0') ? true : false;
		$this->options->noindex = ($this->params->get('noindex') != NULL && $this->params->get('noindex') != '0') ? true : false;
		$this->options->blank = ($this->params->get('_blank') != NULL && $this->params->get('_blank') != '0') ? true : false;
		$this->options->fixUri = ($this->params->get('fixuri') != NULL && $this->params->get('fixuri') != '0') ? true : false;
		$this->options->prefix = $this->params->get('externalPrefix'); // todo
		$this->pattern_link = '#<a (.*?)href=[\"\'](.*?)\/\/(.*?)[\"\'](.*?)>(.*?)<\/a>#';


		// excludes
		$this->options->exclude_domains = $this->params->get('excludeDomains');
		if (!empty($this->options->exclude_domains))
			$this->options->exclude_domains = explode("\n", $this->options->exclude_domains);
		
		$doc = & JFactory::getDocument();



		if ($this->options->jsclick)
		{
		$doc->addScriptDeclaration('

		// extlinks plugin
		jQuery(function($) {
			$("[data-link=\'external\']").each(function(a,b) {
				var href = $(b).attr("data-href");
				$(b).attr("href", href);
			});
		});
		');
		}
	}

	function fixUriRedirect($link, $params)
	{
		$objJURI = JFactory::getURI();
		if (
			$params->get('page_title') != NULL &&
			$objJURI->getPath() != $link
		)
		{
			header('Location: '.$link,TRUE,301);
			exit;
		}
	}

	function onK2PrepareContent($item, $params, $limitstart)
	{

		/*if ($this->options->fixUri)
			$this->fixUriRedirect($item->link, $params);*/

		preg_match_all($this->pattern_link, $item->text, $out);

		if (isset($out) && !empty($out[0]))
			for ($i=0;$i<@max(array_keys($out[0]))+1;$i++)
			{
				$linkTag=$out[0][$i];
				if (@$linkTag && (substr(@$out[2][$i],0,4) == "http" || substr(@$out[2][$i],0,5) == "https") &&
					strpos(@$linkTag, $_SERVER['HTTP_HOST']) === false)
				{
					$sink = str_replace('/', '', rawurldecode($out[3][$i]));
					if (!empty($sink))
					{
						if (count($this->options->exclude_domains) > 0)
						{
							$svr = $linkTag;

							if (in_array($sink, $this->options->exclude_domains))
								continue;

							if ($this->options->noindex)
								$svr = '<noindex>' . $svr . '</noindex>';

							if ($this->options->nofollow)
								$svr = str_replace('<a', '<a rel="nofollow" ', $svr);

							if ($this->options->blank)
								$svr = str_replace('<a', '<a target="_blank" ', $svr);

							if ($this->options->jsclick)
								$svr = str_replace('href', 'href="#" data-href', $svr);

							$svr = str_replace('  ', ' ', $svr);
							$svr = str_replace('<a', '<a data-link="external"', $svr);
							$item->text = str_replace($linkTag, $svr, $item->text);
						}
					}
				}
			}

		return '';
	}
}
