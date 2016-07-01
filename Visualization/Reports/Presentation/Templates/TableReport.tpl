{nocache}
    {foreach $REPORT_DATASET as $REPORT_SECTION_ID=>$REPORT_DATA}
        <table>
        <caption>{$REPORT_SECTION_ID}</caption>
        {foreach $REPORT_DATA as $REPORT_DATA_ID=>$DATA_ROW}
            <tr>
            {foreach $DATA_ROW as $COLVAL}
                <td>{$COLVAL}</td>
            {/foreach}
            </tr>
        {/foreach}
        </table>
    {/foreach}
{/nocache}