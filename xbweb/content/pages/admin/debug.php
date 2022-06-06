<section class="content">
    <h2>Debug</h2>
    <pre>
        <?=var_export(\xbweb\Debug::get(), true)?>
    </pre>
</section>
<section>
    <h2>Table sample</h2>
    <table class="data-table">
        <thead>
        <tr>
            <th class="checker id sortable">
                <input type="hidden" name="table_all_checked" value="2">
                <span>ID</span>
            </th>
            <th class="sortable">Name</th>
            <th class="sortable">Second</th>
            <th class="actions"></th>
        </tr>
        </thead>
        <tbody>
        <tr>
            <td class="checker">
                <input type="hidden" name="table[1][checked]" value="0">
                <span>1</span>
            </td>
            <td>Name 1</td>
            <td>Second 1</td>
            <td class="actions">
                <a href="/admin/users/edit/1" class="icon action"></a>
                <a href="/admin/users/delete/1" class="icon action modal"></a>
                <a href="/admin/users/remove/1" class="icon action modal"></a>
            </td>
        </tr>
        <tr>
            <td class="checker">
                <input type="hidden" name="table[2][checked]" value="0">
                <span>2</span>
            </td>
            <td>Name 2</td>
            <td>Second 2</td>
            <td class="actions">
                <a href="/admin/users/edit/2" class="icon action"></a>
                <a href="/admin/users/delete/2" class="icon action modal"></a>
                <a href="/admin/users/remove/2" class="icon action modal"></a>
            </td>
        </tr>
        <tr>
            <td class="checker">
                <input type="hidden" name="table[3][checked]" value="0">
                <span>3</span>
            </td>
            <td>Name 3</td>
            <td>Second 0</td>
            <td class="actions">
                <a href="/admin/users/edit/3" class="icon action"></a>
                <a href="/admin/users/delete/3" class="icon action modal"></a>
                <a href="/admin/users/remove/3" class="icon action modal"></a>
            </td>
        </tr>
        </tbody>
    </table>
</section>

<section class="form">
    <form method="post" action="/index.php">
        <h2>Some form caption</h2>

        <nav class="tabs">
            <a href="#form-category-0" class="">Fields</a>
            <a href="#form-category-1" class="active">Plugins</a>
            <a href="#form-category-2" class="">Actions ACL</a>
            <a href="#form-category-3" class="">Fields ACL</a>
            <a href="#form-category-4" class="">Files</a>
        </nav>

        <section id="form-category-0" class="tab">
            <fieldset>
                <legend>String fields</legend>
                <label class="fc-string">
                    <span>String</span>
                    <input type="text" name="f-string" placeholder="String">
                </label>
                <label class="fc-password">
                    <span>Password</span>
                    <input type="password" name="f-password" placeholder="Password">
                </label>
                <label class="fc-search">
                    <span>Search</span>
                    <input type="search" name="f-search" placeholder="Search">
                </label>
                <label class="fc-email required">
                    <span>E-Mail</span>
                    <input type="email" name="f-email" placeholder="some@domain.tld" required>
                </label>
                <label class="fc-url">
                    <span>URL</span>
                    <input type="url" name="f-url" placeholder="https://domain.tld">
                </label>
                <label class="fc-phone">
                    <span>Phone</span>
                    <input type="tel" name="f-phone" placeholder="+7 (xxx) xxx-xx-xx">
                </label>
            </fieldset>

            <fieldset>
                <legend>Click fields</legend>
                <label class="fc-boolean">
                    <input type="checkbox" name="f-checkbox">
                    <span>Boolean</span>
                </label>
                <label class="fc-boolean">
                    <input class="power" type="checkbox" name="f-power">
                    <span>Switcher</span>
                </label>
                <label class="fc-radio">
                    <input type="radio" name="f-radio" value="v1">
                    <span>Radio 1</span>
                </label>
                <label class="fc-radio">
                    <input type="radio" name="f-radio" value="v2">
                    <span>Radio 2</span>
                </label>
            </fieldset>

            <fieldset>
                <legend>Boolean controls</legend>
                <label class="fc-boolean">
                    <input class="switch" type="checkbox" name="f-switcher">
                    <span>Switcher</span>
                </label>
                <label class="fc-boolean">
                    <input class="visibility" type="checkbox" name="f-visibility">
                    <span>Visibility</span>
                </label>
                <label class="fc-boolean">
                    <input class="bell" type="checkbox" name="f-bell">
                    <span>Bell</span>
                </label>
                <label class="fc-boolean">
                    <input class="sound" type="checkbox" name="f-sound">
                    <span>Sound</span>
                </label>
            </fieldset>

            <fieldset>
                <legend>Flags</legend>
                <label class="fc-boolean">
                    <input type="checkbox" name="f-flags" value="1">
                    <span>FLag 1</span>
                </label>
                <label class="fc-boolean">
                    <input type="checkbox" name="f-flags" value="2">
                    <span>FLag 2</span>
                </label>
                <label class="fc-boolean">
                    <input type="checkbox" name="f-flags" value="4">
                    <span>FLag 3</span>
                </label>
                <label class="fc-boolean">
                    <input type="checkbox" name="f-flags" value="8">
                    <span>FLag 4</span>
                </label>
            </fieldset>
        </section>

        <section id="form-category-1" class="active tab">
            <fieldset>
                <legend>Text fields</legend>
                <label class="fc-text">
                    <span>Tiny</span>
                    <textarea name="f-tinytext" placeholder="Tiny text"></textarea>
                </label>
                <label class="fc-text full">
                    <span>WYSiWYG</span>
                    <textarea class="wysiwyg allowed-links allowed-images" name="f-wysiwyg" placeholder="WYSiWYG"></textarea>
                </label>
                <label class="fc-text full">
                    <span>Code</span>
                    <textarea class="source" name="f-source"></textarea>
                </label>
            </fieldset>
        </section>

        <section id="form-category-2" class="tab">
            <fieldset>
                <legend>Users</legend>
                <div class="acl-action">
                    <span>Index</span>
                    <label class="fc-radio">
                        <input type="radio" name="users-index" value="deny">
                        <span>Deny</span>
                    </label>
                    <label class="fc-radio">
                        <input type="radio" name="users-index" value="allow">
                        <span>Allow</span>
                    </label>
                    <label class="fc-radio">
                        <input type="radio" name="users-index" value="default">
                        <span>Default</span>
                    </label>
                    <span class="default allowed">Allowed</span>
                </div>
                <div class="acl-action">
                    <span>Create</span>
                    <label class="fc-radio">
                        <input type="radio" name="users-create" value="deny">
                        <span>Deny</span>
                    </label>
                    <label class="fc-radio">
                        <input type="radio" name="users-create" value="allow">
                        <span>Allow</span>
                    </label>
                    <label class="fc-radio">
                        <input type="radio" name="users-create" value="default">
                        <span>Default</span>
                    </label>
                    <span class="default denied">Denied</span>
                </div>
                <div class="acl-action">
                    <span>Update</span>
                    <label class="fc-radio">
                        <input type="radio" name="users-update" value="deny">
                        <span>Deny</span>
                    </label>
                    <label class="fc-radio">
                        <input type="radio" name="users-update" value="allow">
                        <span>Allow</span>
                    </label>
                    <label class="fc-radio">
                        <input type="radio" name="users-update" value="default">
                        <span>Default</span>
                    </label>
                    <span class="default denied">Denied</span>
                </div>
                <div class="acl-action">
                    <span>Delete</span>
                    <label class="fc-radio">
                        <input type="radio" name="users-delete" value="deny">
                        <span>Deny</span>
                    </label>
                    <label class="fc-radio">
                        <input type="radio" name="users-delete" value="allow">
                        <span>Allow</span>
                    </label>
                    <label class="fc-radio">
                        <input type="radio" name="users-delete" value="default">
                        <span>Default</span>
                    </label>
                    <span class="default denied">Denied</span>
                </div>
            </fieldset>
        </section>

        <section id="form-category-3" class="tab">
            <fieldset>
                <legend>Basic</legend>
                <div class="acl-action">
                    <span>Anonimous users</span>
                    <label class="fc-radio"><input type="checkbox" name="anonimous-update"><span>Update</span></label>
                    <label class="fc-radio"><input type="checkbox" name="anonimous-read"><span>Read</span></label>
                    <label class="fc-radio"><input type="checkbox" name="anonimous-create"><span>Create</span></label>
                </div>
                <div class="acl-action">
                    <span>Users</span>
                    <label class="fc-radio"><input type="checkbox" name="user-update"><span>Update</span></label>
                    <label class="fc-radio"><input type="checkbox" name="user-read"><span>Read</span></label>
                    <label class="fc-radio"><input type="checkbox" name="user-create"><span>Create</span></label>
                </div>
                <div class="acl-action">
                    <span>Managers</span>
                    <label class="fc-radio"><input type="checkbox" name="manager-update"><span>Update</span></label>
                    <label class="fc-radio"><input type="checkbox" name="manager-read"><span>Read</span></label>
                    <label class="fc-radio"><input type="checkbox" name="manager-create"><span>Create</span></label>
                </div>
                <div class="acl-action">
                    <span>Administrators</span>
                    <label class="fc-radio"><input type="checkbox" name="admin-update"><span>Update</span></label>
                    <label class="fc-radio"><input type="checkbox" name="admin-read"><span>Read</span></label>
                    <label class="fc-radio"><input type="checkbox" name="admin-create"><span>Create</span></label>
                </div>
                <div class="acl-action">
                    <span>Inactive users</span>
                    <label class="fc-radio"><input type="checkbox" name="inactive-update"><span>Update</span></label>
                    <label class="fc-radio"><input type="checkbox" name="inactive-read"><span>Read</span></label>
                    <label class="fc-radio"><input type="checkbox" name="inactive-create"><span>Create</span></label>
                </div>
            </fieldset>
        </section>

        <section id="form-category-4" class="tab">
            <fieldset>
                <legend>Single</legend>
                <label class="fc-file">
                    <span>Some file</span>
                    <input type="file" name="some-single-file">
                    <span class="fc-static-text">/some/file</span>
                </label>
            </fieldset>

            <fieldset class="repeater" data-max-items="5">
                <legend>Files</legend>
                <div class="buttons">
                    <button class="add">Add file</button>
                </div>
                <div class="sample">
                    <input type="hidden" data-name="files[+id+][id]" value="0">
                    <label class="fc-string">
                        <span>Title</span>
                        <input type="text" data-name="files[+id+][title]" value="">
                    </label>
                    <label class="fc-file">
                        <span>File</span>
                        <input type="file" data-name="files[+id+][file]">
                        <button class="delete"></button>
                        <span class="fc-static-text"></span>
                    </label>
                </div>
            </fieldset>

            <fieldset class="repeater" data-max-items="5">
                <legend>Images</legend>
                <div class="buttons">
                    <button class="add">Add image</button>
                </div>
                <div class="sample">
                    <input type="hidden" data-name="images[+id+][id]" value="0">
                    <label class="fc-string">
                        <span>Title</span>
                        <input type="text" data-name="images[+id+][title]" value="">
                    </label>
                    <label class="fc-file">
                        <span>File</span>
                        <input type="file" data-name="images[+id+][file]">
                        <button class="delete"></button>
                        <span class="fc-static-text"></span>
                    </label>
                    <label class="fc-text">
                        <span>Description</span>
                        <textarea data-name="images[+id+][description]"></textarea>
                    </label>
                    <label>
                        <span class="image" style="background-image: url('/content/images/cms.png')"></span>
                    </label>
                </div>
            </fieldset>
        </section>

        <div class="buttons">
            <input type="submit" value="Save">
            <input type="reset" value="Reset">
            <button class="delete">Delete</button>
            <button class="yellow">Yellow</button>
            <button class="add">Add</button>
        </div>
    </form>
</section>
