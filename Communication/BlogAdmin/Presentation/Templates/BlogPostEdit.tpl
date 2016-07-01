{extends file=$EXTENSION_SETTINGS.LayoutTemplate}
{block name="PAGE_CONTENT"}
    {nocache}
    <div id="ContentSection">
        <h1>Edit Blog Post</h1>
        <form class="Auto Grid Layout" method="Post" action="BlogPostEdit">
            <div class="Row">
                <div class="Spans3">
                    <label>Category</label>
                    <select name="BlogPostCategory">
                        {foreach $BLOG_CATEGORIES as $BLOG_CATEGORY}
                            <option value="{$BLOG_CATEGORY->Get_BlogCategoryId()}" {if $BLOG_CATEGORY->Get_BlogCategoryId() == $EDIT_BLOG_POST->Get_BlogCategoryId()}selected{/if}>{$BLOG_CATEGORY->Get_Title()}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="Spans6">
                    <label>Title</label>
                    <input type="text" name="BlogPostTitle" value="{$EDIT_BLOG_POST->Get_Title()|escape:"html"}" />
                </div>
                <div class="Spans3">
                    <label>Is Published</label>
                    <input type="checkbox" name="BlogPostIsPublished" value="1" {if $EDIT_BLOG_POST->Get_IsPublished()}checked{/if} />
                </div>
            </div>
            <div class="Row">
                <div class="Spans12">
                    <label>Post Contents HTML</label>
                    <textarea class="BlogPostContents" name="BlogPostContents">{$EDIT_BLOG_POST->Get_Contents()|escape:"html"}</textarea>
                </div>
            </div>
            <input type="hidden" name="BlogPostId" value="{$EDIT_BLOG_POST->Get_BlogPostId()}" />
            <input type="hidden" name="Action" value="SavePost" />
            <button class="Primary" type="submit">Save Post</button>
            <a href="./" class="Button">Cancel</a>
        </form>
    </div>
    {/nocache}
{/block}