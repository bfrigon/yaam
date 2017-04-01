<h2>Strings</h2>

<h3>To lowercase</h3>
<p><b>Syntax:</b> <raw>[[ var_name | lower ]]</raw></p>
<p>
    <raw>[[ $var_test | lower ]]</raw> = [[ $var_test | lower ]]
</p>

<h3>To uppercase</h3>
<p><b>Syntax:</b> <raw>[[ var_name | upper ]]</raw></p>
<p>
    <raw>[[ $var_test | upper ]]</raw> = [[ $var_test | upper ]]
</p>

<h3>First character uppercase</h3>
<p><b>Syntax:</b> <raw>[[ var_name | ucfirst ]]</raw></p>
<p>
    <raw>[[ $var_test | ucfirst ]]</raw> = [[ $var_test | ucfirst ]]
</p>

<h3>First character of each uppercase</h3>
<p><b>Syntax:</b> <raw>[[ var_name | ucwords ]]</raw></p>
<p>
    <raw>[[ $var_test | ucwords ]]</raw> = [[ $var_test | ucwords ]]
</p>

<h3>Word wrap</h3>
<p><b>Syntax:</b> <raw>[[ var_name | wrap:(number of characters) ]]</raw></p>
<p>
    <raw>[[ $var_test | wrap:8 ]]</raw> = [[ $var_test | wrap:8 ]]
</p>

<h3>Length</h3>
<p><b>Syntax:</b> <raw>[[ var_name | length ]]</raw></p>
<p>
    <raw>[[ $var_test | length ]]</raw> = [[ $var_test | length ]]<br />
    <raw>[[ $var_array_single | length ]]</raw> = [[ $var_array_single | length ]]
</p>

<h3>Ellipses</h3>
<p><b>Syntax:</b> <raw>[[ var_name | ellipses:(number of characters) ]]</raw></p>
<p>
    <raw>[[ $var_test | ellipses:10 ]]</raw> = [[ $var_test | ellipses:10 ]]
</p>

<h3>Pluralize</h3>
<p><b>Syntax:</b> <raw>[[ var_name | pluralize : "items" : "item" ]]</raw></p>
<p>
    <raw>[[ $var_count_23 | pluralize : "items" : "item" ]]</raw> = [[ var_count_23 | pluralize : "items" : "item" ]]<br />
    <raw>[[ $var_count_1 | pluralize : "items" : "item" ]]</raw> = [[ var_count_1 | pluralize : "items" : "item" ]]
</p>

<h3>Format phone number</h3>
<p><b>Syntax:</b> <raw>[[ var_name | format_phone ]]</raw></p>
<p>
    <raw>[[ $var_phone | format_phone ]]</raw> = [[ $var_phone | format_phone ]]
</p>

<h3>Format time (seconds)</h3>
<p><b>Syntax:</b> <raw>[[ var_name | format_time_seconds ]]</raw></p>
<p>
    <raw>[[ $var_integer_567 | format_time_seconds ]]</raw> = [[ $var_integer_567 | format_time_seconds ]]
</p>

<h3>Format money</h3>
<p><b>Syntax:</b> <raw>[[ var_name | format_money ]]</raw></p>
<p>
    <raw>[[ $var_float_23_11 | format_money ]]</raw> = [[ $var_float_23_11 | format_money ]]
</p>


<h2>Array</h2>

<h3>Count</h3>
<p><b>Syntax:</b> <raw>[[ $variable | count ]]</raw></p>
<p>
    <raw>[[ $var_array_single | count ]]</raw> = [[ $var_array_single | count ]] items<br />
    <raw>[[ $var_test | count ]]</raw> = [[ $var_test | count ]] item
</p>

<h3>Limit</h3>
<p><b>Syntax:</b> <raw>[[ $variable | limit:(number of items) ]]</raw></p>
<p>
    <raw>[[ $var_array_single | limit:2 ]]</raw> = <code><pre>[[ $var_array_single | limit:2 | var_dump ]]</pre></code>
</p>

<h3>Read first item</h3>
<p><b>Syntax:</b> <raw>[[ $variable | first ]]</raw></p>
<p>
    <raw>[[ $var_array_single | first ]]</raw> = [[ $var_array_single | first | var_dump ]]
</p>

<h3>Read last item</h3>
<p><b>Syntax:</b> <raw>[[ $variable | last ]]</raw></p>
<p>
    <raw>[[ $var_array_single | last ]]</raw> = [[ $var_array_single | last | var_dump ]]
</p>

<h3>Read associative array</h3>
<p><b>Syntax:</b> <raw>[[ $variable.item ]]</raw></p>
<p>
    <raw>[[ $var_array_dict.item2 ]]</raw> = [[ $var_array_dict.item2 | var_dump]]<br />
    <raw>[[ $var_array_dict.undefined ]]</raw> = [[ $var_array_dict.undefined | var_dump]]<br />
    <raw>[[ $var_null.item2 ]]</raw> = [[ $var_null.item2 | var_dump]]<br />
</p>

<h3>Read indexed array</h3>
<p><b>Syntax:</b> <raw>[[ $variable.item ]]</raw></p>
<p>
    <raw>[[ $var_array_single | index:2 ]]</raw> = [[ $var_array_single | index:2 | var_dump]]<br />
    <raw>[[ $var_array_single | index:40 ]]</raw> = [[ $var_array_single | index:40 | var_dump]]<br />
    <raw>[[ $var_null | index:2 ]]</raw> = [[ $var_null | index:2 | var_dump]]<br />
</p>

<h3>Read multidimentional array</h3>
<p><b>Syntax:</b> <raw>[[ $variable.item.subitem ]]</raw></p>
<p>
    <raw>[[ $var_array_multi.item1.subitem2 ]]</raw> = [[ $var_array_multi.item1.subitem2 | var_dump]]<br />
    <raw>[[ $var_array_multi.item1.undefined ]]</raw> = [[ $var_array_multi.item1.undefined | var_dump]]<br />
    <raw>[[ $var_null.item1.subitem2 ]]</raw> = [[ $var_null.item1.subitem2 | var_dump]]<br />
</p>

<h3>Read associative array with index</h3>
<p><b>Syntax:</b> <raw>[[ $variable.item ]]</raw></p>
<p>
    <raw>[[ $var_array_dict | to_indexed | index:2 ]]</raw> = [[ $var_array_dict | to_indexed | index:2 | var_dump]]<br />
    <raw>[[ $var_array_dict | to_indexed | index:40 ]]</raw> = [[ $var_array_dict | to_indexed | index:40 | var_dump]]<br />
    <raw>[[ $var_null | to_indexed | index:2 ]]</raw> = [[ $var_null | to_indexed | index:2 | var_dump]]<br />
</p>

