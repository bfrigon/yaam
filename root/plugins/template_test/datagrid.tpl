<!--SYNTAX:
<datagrid class="[[$class]]" data-type="array" data-source="test_grid_array_multi">
    <caption class="custom-caption-style">Source type: Array, Source: Multidimentional array</caption>

    <header>
        <column type="key" class="additional-column-style">key</column>
        <column type="data">value.name</column>
        <column>row.phone</column>
        <column>$row</column>
        <column>row</column>
        <column>var_test</column>
        <column>row.data.test</column>
    </header>

    <row class="extra-class-row" id="test">
        <column type="test">[[ KEY ]]</column>
        <column class="extra-class-column">[[ value.name ]]</column>
        <column>[[ row.phone | format_phone ]]</column>
        <column id="id-column-test">[[ $row ]]</column>
        <column>[[ row ]]</column>
        <column>[[ var_test ]]</column>
        <column>[[ row.data.test ]]</column>
    </row>

    <footer>
        <column id="id-column-footer" colspan="7">Footer</column>
    </footer>

    <if-empty>Empty array</if-empty>
</datagrid>
-->

<datagrid class="[[$class]]" data-type="array" data-source="test_grid_array_multi">
    <caption class="custom-caption-style">Source type: Array, Source: Multidimentional array</caption>

    <header>
        <column type="key" class="additional-column-style">key</column>
        <column type="data">value.name</column>
        <column>row.phone</column>
        <column>$row</column>
        <column>row</column>
        <column>var_test</column>
        <column>row.data.test</column>
    </header>

    <row class="extra-class-row" id="test">
        <column type="test">[[ KEY ]]</column>
        <column class="extra-class-column">[[ value.name ]]</column>
        <column>[[ row.phone | format_phone ]]</column>
        <column id="id-column-test">[[ $row ]]</column>
        <column>[[ row ]]</column>
        <column>[[ var_test ]]</column>
        <column>[[ row.data.test ]]</column>
    </row>

    <footer>
        <column id="id-column-footer" colspan="7">Footer</column>
    </footer>

    <if-empty>Empty array</if-empty>
</datagrid>

<datagrid data-type="array" data-source="test_grid_array_single">
    <caption>Source type: Array, Source: Simple array</caption>

    <header>
        <column>key</column>
        <column>row</column>
    </header>

    <row>
        <column>[[ KEY ]]</column>
        <column>[[ row ]]</column>
    </row>

    <if-empty>Empty array</if-empty>
</datagrid>

<datagrid data-type="array" data-source="test_grid_array_null">
    <caption>Source type: Array, Source : NULL</caption>

    <header>
        <column type="key" class="additional-column-style">key</column>
        <column>row</column>
    </header>

    <row>
        <column>[[ KEY ]]</column>
        <column>[[ row ]]</column>
    </row>

    <if-empty>Null array</if-empty>
</datagrid>

<datagrid data-type="array" data-source="test_grid_array_unset">
    <caption>Source type: Array, Source : Undefined variable</caption>

    <header>
        <column type="key" class="additional-column-style">key</column>
        <column>row</column>
    </header>

    <row>
        <column>[[ KEY ]]</column>
        <column>[[ row ]]</column>
    </row>

    <if-empty>Empty array</if-empty>
</datagrid>
