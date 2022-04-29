Nette.validators['PacketeryModuleFormValidators_greaterThan'] = (elem, args, val) => {
    return parseFloat(val) > args;
};
