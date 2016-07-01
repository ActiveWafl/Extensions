{extends file=$LAYOUT_FILE}
{block name="PAGE_CONTENT"}
    <link rel="stylesheet" type="text/css" href="/{$REPORT_CSS}" />
    {include file=$REPORT_TEMPLATE}
    <div style="text-align: center;">
        <button onclick="window.print();">Print</button>
        <a class="Button" href="?{$QUERY_STRING}&amp;Format=csv">Download .csv</a>
        <a href="?" class="Button">Back to Reports</a>
    </div>
{/block}