<div class="box dialog warning">
    <div>
        You are about to delete the following user(s) :

        <foreach type="list" data-type="odbc" data-source="results">
            [[user]] ([[fullname]])
        </foreach>

        <strong>This action cannot be reversed!</strong>
    </div>

    <toolbar class="center">
        <item type="button" icon="delete" action="delete" params="confirm=1" keep-uri keep-referrer >Delete</item>
        <item type="button" icon="cancel" action="cancel">Cancel</item>
    </toolbar>
</div>
