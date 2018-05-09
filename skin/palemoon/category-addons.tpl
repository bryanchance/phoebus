<h1>{$PAGE_TITLE}</h1>

{if $PAGE_TYPE == 'cat-all-extensions'}
<p>
  Extensions are small add-ons that add new functionality to Pale Moon, from a simple toolbar button to a completely new feature. They allow you to customize the browser to fit your own needs and preferences, while letting us keep the core itself light and lean.
</p>
{elseif $PAGE_TYPE == 'cat-themes'}
<p>
  Themes allow you to change the look and feel of the user interface and personalize it to your tastes. A theme can simply change the colors of the UI or it can change every aspect of its appearance.
</p>
{elseif $PAGE_TYPE == 'search'}
<p style="margin-left: 20px;>
  <form id="addonSearchForm" method="get" action="/search/">
    <input
      type="text"
      class="addonSearchInput"
      name="terms"
{if $SEARCH_TERMS}
      value="{$SEARCH_TERMS}"
{/if}
      placeholder="Find add-ons..."
      size="44"
      maxlength="64"
    >
    <input type="submit" value="Search" class="addonSearchButton">
  </form>
</p>
{/if}

{if $PAGE_TYPE == 'cat-extensions' || $PAGE_TYPE == 'cat-all-extensions' || $PAGE_TYPE == 'cat-themes' || $PAGE_TYPE == 'search'}
<div>
{foreach $PAGE_DATA as $key}
  <a
    href="{$key.url}"
{if $key.type == 'external'}
    target="_blank"
    title="This add-on is hosted independently"
    class="fake-table-row category-addon real-externals"
{else}
    class="fake-table-row category-addon hosted-extensions"
{/if}
    >

    <img src="{$key.icon}" class="category-addon-icon alignleft" width="32px" height="32px" />

{if $PAGE_TYPE == 'cat-themes'}
    <div class="category-theme-preview alignright" style="background-image: url('{$key.preview}');"> </div>
{/if}
    
    <div class="category-addon-content"><strong>{$key.name}</strong>
{if $key.type == 'external'}
      <small>[External]</small>
{/if}
      <br />
      <small>{$key.description}</small>
    </div>
  </a>
{/foreach}
</div>
{/if}

{if $PAGE_TYPE == 'cat-extensions' || $PAGE_TYPE == 'cat-all-extensions'}
</div> <!-- END DIV ID PM-Content-Body -->
<div id="PM-Content-Sidebar"> <!-- START PM-Content-Sidebar -->
  <div class="category-extensions-list">
    <h1>Categories</h1>
    <a href="/extensions/alerts-and-updates/">Alerts &amp; Updates</a><br />
    <a href="/extensions/appearance/">Appearance</a><br />
    <a href="/extensions/bookmarks-and-tabs/">Bookmarks &amp; Tabs</a><br />
    <a href="/extensions/download-management/">Download Management</a><br />
    <a href="/extensions/feeds-news-and-blogging/">Feeds, News, &amp; Blogging</a><br />
    <a href="/extensions/privacy-and-security/">Privacy &amp; Security</a><br />
    <a href="/extensions/search-tools/">Search Tools</a><br />
    <a href="/extensions/social-and-communication/">Social &amp; Communication</a><br />
    <a href="/extensions/tools-and-utilities/">Tools &amp; Utilities</a><br />
    <a href="/extensions/web-development/">Web Development</a><br />
    <a href="/extensions/other/">Other</a><br />
  </div>
  <div class="clearfix"></div>
{/if}
{$key = null}