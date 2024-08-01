Nette.validators['PacketeryModuleFormValidators_greaterThan'] = (elem, args, val) => {
    return parseFloat(val) > args;
};

Nette.validators['PacketeryModuleFormValidators_dateIsLater'] = (elem, args, val) => {
    return new Date(val).getTime() > new Date(args).getTime();
};

Nette.validators['PacketeryModuleFormValidators_dateIsInMysqlFormat'] = (elem, args, val) => {
    var testDate = new Date( val );
    return ( val.length === 10 && !isNaN( testDate.getTime() ) && testDate.toISOString().startsWith( val ) );
};

Nette.validators[ 'PacketeryModuleFormValidators_dimensionValidate' ] = ( elem, args, val ) => {
    var dimension = parseFloat( val.replace( ',', '.' ) );
	if ( dimension < 0 ) {
		return false;
	}

    if ( args === 'mm' ) {
        return Number.isInteger( dimension );
    }

	return !isNaN( dimension );
};
