Nette.validators['PacketeryModuleFormValidators_greaterThan'] = (elem, args, val) => {
    return parseFloat(val) > args;
};

Nette.validators['PacketeryModuleFormValidators_dateIsLater'] = (elem, args, val) => {
    return new Date(val).getTime() > new Date(args).getTime();
};
