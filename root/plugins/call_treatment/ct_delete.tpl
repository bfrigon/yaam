<dialog type="warning">
    <message>
        You are about to delete the following call treatment rule(s) :

        <foreach type="list" data-type="odbc" data-source="results">
            [[description]] ([[extension]],[[caller_num]],[[caller_name]])
        </foreach>

        <strong>This action cannot be reversed!</strong>
    </message>

    <toolbar class="center">
        <item type="button" icon="delete" action="delete" params="confirm=1" keep-uri keep-referrer >Delete</item>
        <item type="button" icon="cancel" action="cancel">Cancel</item>
    </toolbar>
</dialog>
