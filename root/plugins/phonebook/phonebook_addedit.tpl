<form id="frm_addedit_phone" method="post" keep-referrer>
    <dialog>
        <if type="is" name="action" value="add"><title>Add phone number</title></if>
        <if type="is" name="action" value="edit"><title>Edit phone number</title></if>


        <if type="perm" value="phonebook_all_users">
            <field name="extension" type="text" caption="Owner (extension)" value="[[$pb_data@extension]]">
                <help>Extension to which this phone book entry belongs.</help>
            </field>
        </if>

        <field name="speed_dial" type="text" caption="Speed dial" value="[[$pb_data@speed_dial]]">
            <help>Speed dial number to assign to this phonebook entry (e.g. 00 can be accessed by dialing [[$speed_dial_prefix]]00)</help>
        </field>

        <field name="number" type="text" caption="Number" value="[[$pb_data@number]]" />

        <field name="name" type="text" caption="Name" value="[[$pb_data@name]]">
            <help>Override caller id name for incoming calls from this number.</help>
        </field>

        <field name="notes" type="text" caption="Notes" value="[[$pb_data@notes]]" />

        <toolbar class="center">
            <item type="submit" name="submit" icon="save">Save</item>
            <item type="button" action="cancel" icon="cancel">Cancel</item>
        </toolbar>
    </dialog>
</form>
