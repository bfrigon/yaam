<dialog type="warning">
    <message>
        You are about to delete the following message(s) :

        <foreach type="list" data-type="odbc" data-source="results">
            <call name="regex_clid" params="[[callerid]]" return="clid_name,clid_number" />
            From : [[$clid_number | format_phone]] ([[$clid_name]]) at
            [[origtime | format_unix_time]]
        </foreach>

        <strong>This action cannot be reversed!</strong>
    </message>

    <toolbar class="center">
        <item type="button" icon="delete" action="delete" params="confirm=1" keep-uri keep-referrer >Delete</item>
        <item type="button" icon="cancel" action="cancel">Cancel</item>
    </toolbar>
</dialog>
