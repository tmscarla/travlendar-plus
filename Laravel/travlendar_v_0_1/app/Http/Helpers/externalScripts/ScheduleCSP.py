from constraint import *
import json
import sys
import itertools

def main(args):
	"""
	Finds a feasible schedule
	Solves the CSP problem associated with finding valid assignments 
	for the bounds of flexbile events in a schedule.
	:param args[1]: Contains the time in seconds of each accepted time slot
	:param args[2]: Contains the information for the variables of the CSP problem
	in a json string of the following format
							  {
								'id'		: id associated to the variable
								'upper'		: unix epoch of upper bound of the variable
								'lower'		: unix epoch of lower bound of the variable
								'travel'	: travel duration in seconds
								'duration'	: minimum duration of the event
							  }
	:return: json representation of the bound associated with each variable. empty is no solution found.
	"""
	problem = Problem()

	slot = int(args[1])

	events = json.loads(args[2])


	for e in events:
		dimension = list(range(e["lower"] - e["travel"], e["upper"], slot))
		dimension.append(e["upper"])
		domain = list(itertools.product(*[dimension,dimension]))

		varId = e["id"]
		problem.addVariables([varId], domain)

		problem.addConstraint((lambda a: a[1] > a[0]), [varId])

		def constraint(a, e=e) : return e["duration"] + e["travel"] < (a[1] - a[0] + 1)

		problem.addConstraint(constraint, [e["id"]])


	for e1 in events:
		for e2 in events:
			if e1["id"] != e2["id"]:
				problem.addConstraint(lambda a,b:
						(not (b[0] < a[1] < b[1])) and
						(not (b[0] < a[0] < b[1])) and
						(not (a[0] < b[0] and a[1] > b[1])
					), (e1["id"], e2["id"])
				)

				problem.addConstraint((lambda a,b:
					a[0] != b[0] and a[1] != b[1] ), (e1["id"], e2["id"]) 
				)			

	solutions = problem.getSolution()
	if solutions is None:
		print(json.dumps({}))
	else:
		for e in events:
			solutions[e["id"]] = (solutions[e["id"]][0] + e["travel"], solutions[e["id"]][1])

		print(json.dumps(solutions))

if __name__ == '__main__':
    main(sys.argv)