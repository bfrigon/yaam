<div class="box dialog error">
    <div>
        You are about to delete the following routes :

        <ul>
        <foreach data-type="odbc" data-source="results">
            <li>[[name]]</li>
        </foreach>
        </ul>

        <strong>This action cannot be reversed!</strong>
    </div>

    <toolbar class="center">
        <item type="button" icon="delete" action="delete" params="confirm=1" keep-uri no-referrer >Delete</item>
        <item type="button" icon="cancel" action="cancel" >Cancel</item>
    </toolbar>
</div>
