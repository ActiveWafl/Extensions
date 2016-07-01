{extends file=$LAYOUT_FILE}
{block name="PAGE_CONTENT"}
    {nocache}
        <form class="Grid Layout">
            <div class="Row">
                <div class="Spans12">
                    <table>
                        {assign var="WAITING_ON_ANY_INPUT" value=false}
                        {foreach $REPORT_INPUTS as $REPORT_INPUT_ID=>$REPORT_INPUT_LABEL}
                            {assign var="WAITING_ON_DEPENDANTS" value=false}
                            {foreach $REPORT_INPUT_DEPENDENCIES as $DEPENDANTID=>$DEPENDANCIES}
                                {if $DEPENDANTID == $REPORT_INPUT_ID}
                                    {foreach $DEPENDANCIES as $DEPENDANCY}
                                        {if !isset($SET_REPORT_INPUTS[$DEPENDANCY])}
                                            {assign var="WAITING_ON_DEPENDANTS" value=true}
                                            {break}
                                        {/if}
                                    {/foreach}
                                {/if}
                            {/foreach}
                            {if !$WAITING_ON_DEPENDANTS}
                            <tr>
                                <td>{$REPORT_INPUT_LABEL}</td>
                                <td>
                                    {if isset($SET_REPORT_INPUTS[$REPORT_INPUT_ID])}
                                        {if $REPORT->GetInputAllowedValues($REPORT_INPUT_ID, $SET_REPORT_INPUTS)|count > 0}
                                            <select name="{$REPORT_INPUT_ID}" readonly>
                                            {foreach $REPORT->GetInputAllowedValues($REPORT_INPUT_ID, $SET_REPORT_INPUTS) as $ALLOWED_VALUE_ID=>$ALLOWED_VALUE_LABEL}
                                                <option value="{$ALLOWED_VALUE_ID}" {if $ALLOWED_VALUE_ID == $SET_REPORT_INPUTS[$REPORT_INPUT_ID]}selected{/if}>{$ALLOWED_VALUE_LABEL}</option>
                                            {/foreach}
                                            </select>
                                        {else}
                                            <input type="text" name="{$REPORT_INPUT_ID}" value="{$SET_REPORT_INPUTS[$REPORT_INPUT_ID]}" readonly />
                                        {/if}
                                    {else}
                                        {if $REPORT->GetInputAllowedValues($REPORT_INPUT_ID, $SET_REPORT_INPUTS)|count > 0}
                                            <select name="{$REPORT_INPUT_ID}">
                                            {foreach $REPORT->GetInputAllowedValues($REPORT_INPUT_ID, $SET_REPORT_INPUTS) as $ALLOWED_VALUE_ID=>$ALLOWED_VALUE_LABEL}
                                                <option value="{$ALLOWED_VALUE_ID}">{$ALLOWED_VALUE_LABEL}</option>
                                            {/foreach}
                                            </select>
                                        {else}
                                            <input type="text" name="{$REPORT_INPUT_ID}" />
                                        {/if}
                                    {/if}
                                </td>
                            </tr>
                            {else}
                                {assign var="WAITING_ON_ANY_INPUT" value=true}
                            {/if}
                        {/foreach}
                    </table>
                    <input name="ReportId" type="hidden" value="{$ReportId}" />
                    <div style="text-align: center;">
                        {if !$WAITING_ON_ANY_INPUT}
                            <input id="ReportFormatInput" name="Format" type="hidden" value="display" />
                            <button onclick="$('ReportFormatInput').SetValue('display');">View / Print</button>
                            <button onclick="$('ReportFormatInput').SetValue('csv');">Download .csv</button>
                        {else}
                            <button>Submit</button>
                        {/if}
                        <a href="?" class="Button">Back to Reports</a>
                    </div>
                </div>
            </div>
        </form>
    {/nocache}
{/block}