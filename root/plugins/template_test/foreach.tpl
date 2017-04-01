<h2>Array</h2>

<h3>Source: Associative array</h3>

<!--SYNTAX:
<foreach data-type="array" data-source="var_array_dict">
    Key: [[ key ]], Value: [[ row ]]
</foreach>
-->

<p>
<foreach data-type="array" data-source="var_array_dict">
    Key: [[ key ]], Value: [[ row ]]<br />
</foreach>
</p>

<h3>Source: Indexed array</h3>

<!--SYNTAX:
<foreach data-type="array" data-source="var_array_single">
    Key: [[ key ]], Value: [[ row ]]
</foreach>
-->
<p>
<foreach data-type="array" data-source="var_array_single">
    Key: [[ key ]], Value: [[ row ]]<br />
</foreach>
</p>

<h3>Source: Multidimentional array</h3>

<!--SYNTAX:
<foreach data-type="array" data-source="var_array_multi">
    Key: [[ key ]], Sub item #1: [[ row.subitem1 ]], Sub item #2: [[ row.subitem2 ]]
</foreach>
-->

<p>
<foreach data-type="array" data-source="var_array_multi">
    Key: [[ key ]], Sub item #1: [[ row.subitem1 | var_dump ]], Sub item #2: [[ row.subitem2 | var_dump ]]<br />
</foreach>
</p>

