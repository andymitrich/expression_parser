###Parser of expressions

This is the parser of special expressions. The parser is going to be used in a process of generating context ads.

Example of expression:<br />
 * <code>"model*func_0,func_1('parameter_1','parameter_2')"</code>
 * <code>"model*func_0,func_2(),include('variable'),!exclude('parameter_1',4)"</code>
 * <code>"model*func_0('variable',!include(func_1(func_2(3))))"</code>

<b>Output:</b>
Instance of class with filled fields: "data", "status", "comment"

###Requirements:
php: >=5.3
