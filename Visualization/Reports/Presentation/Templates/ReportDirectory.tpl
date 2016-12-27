{extends file=$LAYOUT_FILE}
{block name="PAGE_CONTENT"}
    {nocache}
        <div class="ReportDirectory">
            <h1>Reports</h1>
            <ul>
                {foreach $REPORTS as $REPORTID=>$REPORT}
                    <li><a href="?ReportId={$REPORTID}">{$REPORT->Get_Title()}</a>
                {foreachelse}
                    <li>No reports available
                {/foreach}
            </ul>
        </div>
    {/nocache}
{/block}