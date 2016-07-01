{extends file=$EXTENSION_SETTINGS.LayoutTemplate}
{block name="PAGE_CONTENT"}
    <div id="ContentSection">
        <h1>Recent Blog Posts</h1>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Title</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                {nocache}
                {foreach $BLOG_POSTS as $EDIT_BLOG_POST}
                    <tr>
                        <td>{$EDIT_BLOG_POST->Get_PostDate()|datetime_format}</td>
                        <td>{$EDIT_BLOG_POST->Get_Title()}</td>
                        <td>
                            <a class="Button" href="BlogPostEdit?BlogPostId={$EDIT_BLOG_POST->Get_BlogPostId()}">Edit</a>
                        </td>
                    </tr>
                {/foreach}
                {/nocache}
            </tbody>
        </table>
        <a href="BlogPostEdit" class="Button">New Post</a>
    </div>
{/block}